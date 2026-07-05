<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require admin authentication for reset
$session = requireAuth($sessionManager, 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Delete all student allocations
    $query = "DELETE FROM student_allocations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Reset all hall occupied seats to 0
    $query = "UPDATE halls SET occupied_seats = 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $db->commit();
    
    error_log("✅ All allocations reset successfully");
    sendJsonResponse(true, null, null);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('❌ Reset error: ' . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to reset allocations: ' . $e->getMessage());
}
?>
