<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require admin authentication
$session = requireAuth($sessionManager, 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$department = $_GET['department'] ?? '';

if (empty($department)) {
    sendJsonResponse(false, null, 'Department parameter is required');
}

$db = $database->getConnection();

try {
    $recommendations = getRecommendedHallForDepartment($department, $db);
    
    // Format the response
    $formattedRecommendations = [];
    foreach ($recommendations as $rec) {
        $formattedRecommendations[] = [
            'hall_name' => $rec['hall']['name'],
            'venue' => $rec['hall']['venue'],
            'score' => round($rec['score'], 2),
            'available_seats' => $rec['stats']['available_seats'],
            'occupancy_rate' => $rec['stats']['occupancy_rate'],
            'department_count' => $rec['stats']['department_distribution'],
            'violations' => $rec['clustering']['violations'],
            'is_valid' => $rec['clustering']['is_valid']
        ];
    }
    
    sendJsonResponse(true, $formattedRecommendations, null);

} catch (Exception $e) {
    error_log('Hall recommendation error: ' . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to get hall recommendations');
}
?>
