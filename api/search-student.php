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

$searchType = $_POST['search_type'] ?? '';
$searchTerm = trim($_POST['search_term'] ?? '');

if (empty($searchType) || empty($searchTerm)) {
    sendJsonResponse(false, null, 'Search type and term are required');
}

$db = $database->getConnection();

try {
    if ($searchType === 'matric') {
        // Search by matric number (exact match)
        $query = "SELECT s.*, sa.hall_name, sa.seat_number, sa.exam_period, sa.exam_date 
                  FROM students s 
                  LEFT JOIN student_allocations sa ON s.matric_number = sa.matric_number AND sa.exam_date = CURDATE()
                  WHERE s.matric_number = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([strtoupper($searchTerm)]);
    } else {
        // Search by name (partial match)
        $query = "SELECT s.*, sa.hall_name, sa.seat_number, sa.exam_period, sa.exam_date 
                  FROM students s 
                  LEFT JOIN student_allocations sa ON s.matric_number = sa.matric_number AND sa.exam_date = CURDATE()
                  WHERE s.full_name LIKE ?";
        $stmt = $db->prepare($query);
        $stmt->execute(['%' . $searchTerm . '%']);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        sendJsonResponse(false, null, 'No students found matching your search');
    }
    
    // Format the results
    $formattedResults = [];
    foreach ($students as $student) {
        $allocation = null;
        if ($student['hall_name']) {
            $allocation = [
                'hall_name' => $student['hall_name'],
                'seat_number' => $student['seat_number'],
                'exam_period' => $student['exam_period'],
                'exam_date' => $student['exam_date']
            ];
        }
        
        $formattedResults[] = [
            'matric_number' => $student['matric_number'],
            'full_name' => $student['full_name'],
            'department' => $student['department'],
            'level' => $student['level'],
            'email' => $student['email'],
            'allocation' => $allocation
        ];
    }
    
    // Log the search (optional)
    $query = "INSERT INTO student_searches (admin_id, search_term, search_type, search_result) 
              VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $session['user_id'],
        $searchTerm,
        $searchType,
        json_encode(['results_count' => count($formattedResults)])
    ]);
    
    sendJsonResponse(true, $formattedResults, null);
    
} catch (Exception $e) {
    error_log('Student search error: ' . $e->getMessage());
    sendJsonResponse(false, null, 'Search failed: ' . $e->getMessage());
}
?>