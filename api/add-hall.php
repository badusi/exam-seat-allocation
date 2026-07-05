<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require admin authentication
$session = requireAuth($sessionManager, 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$name = trim($_POST['name'] ?? '');
$venue = trim($_POST['venue'] ?? '');
$totalSeats = intval($_POST['totalSeats'] ?? 0);

if (empty($name) || empty($venue) || $totalSeats <= 0 || $totalSeats > 1000) {
    sendJsonResponse(false, null, 'Please provide valid hall details');
}

$db = $database->getConnection();

try {
    // Check if hall name already exists
    $query = "SELECT id FROM halls WHERE name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$name]);
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(false, null, 'A hall with this name already exists');
    }

    // Insert new hall
    $query = "INSERT INTO halls (name, venue, total_seats, occupied_seats) VALUES (?, ?, ?, 0)";
    $stmt = $db->prepare($query);
    $stmt->execute([$name, $venue, $totalSeats]);

    // Get the created hall
    $hallId = $db->lastInsertId();
    $query = "SELECT * FROM halls WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallId]);
    $hall = $stmt->fetch(PDO::FETCH_ASSOC);

    sendJsonResponse(true, $hall, null);

} catch (Exception $e) {
    error_log('Add hall error: ' . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to add hall');
}
?>
