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

$db = $database->getConnection();

try {
    // Get all allocations
    $query = "SELECT * FROM student_allocations ORDER BY hall_name, seat_number";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all halls
    $query = "SELECT * FROM halls ORDER BY created_at";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $halls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for violations
    $violations = [];
    $departmentDistribution = [];
    
    foreach ($allocations as $allocation) {
        $dept = $allocation['department'];
        $hall = $allocation['hall_name'];
        
        if (!isset($departmentDistribution[$dept])) {
            $departmentDistribution[$dept] = [];
        }
        if (!isset($departmentDistribution[$dept][$hall])) {
            $departmentDistribution[$dept][$hall] = 0;
        }
        $departmentDistribution[$dept][$hall]++;
    }
    
    // Check for adjacent same departments
    foreach ($halls as $hall) {
        $hallAllocations = array_filter($allocations, function($a) use ($hall) {
            return $a['hall_name'] === $hall['name'];
        });
        
        $seatMap = [];
        foreach ($hallAllocations as $allocation) {
            $seatMap[$allocation['seat_number']] = $allocation['department'];
        }
        
        foreach ($seatMap as $seatNumber => $department) {
            $adjacentSeats = getAdjacentSeatsForValidation($seatNumber, 10, $hall['total_seats']);
            
            foreach ($adjacentSeats as $adjacentSeat) {
                if (isset($seatMap[$adjacentSeat]) && $seatMap[$adjacentSeat] === $department) {
                    $violations[] = [
                        'hall' => $hall['name'],
                        'department' => $department,
                        'seat1' => $seatNumber,
                        'seat2' => $adjacentSeat
                    ];
                }
            }
        }
    }
    
    $data = [
        'total_allocations' => count($allocations),
        'total_halls' => count($halls),
        'violation_count' => count($violations),
        'violations' => $violations,
        'department_distribution' => $departmentDistribution,
        'allocations' => $allocations,
        'halls' => $halls
    ];
    
    sendJsonResponse(true, $data, null);
    
} catch (Exception $e) {
    error_log('Debug error: ' . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to get debug data: ' . $e->getMessage());
}
?>
