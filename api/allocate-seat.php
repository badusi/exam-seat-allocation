<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require student authentication
$session = requireAuth($sessionManager, 'student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$matricNumber = $_POST['matric_number'] ?? '';
$department = extractDepartment($matricNumber);

if (!in_array($department, getDepartments())) {
    sendJsonResponse(false, null, 'Invalid department: ' . $matricNumber);
}

$db = $database->getConnection();

try {
    // Check if student already has a seat for current exam period
    $query = "SELECT * FROM student_allocations WHERE matric_number = ? AND exam_date = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute([$matricNumber]);
    
    if ($stmt->rowCount() > 0) {
        $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
        sendJsonResponse(true, $allocation, null);
    }

    // Get current exam schedule for the student's department
    $query = "SELECT * FROM exam_schedules 
              WHERE exam_date = CURDATE() 
              AND JSON_CONTAINS(departments, JSON_QUOTE(?)) 
              ORDER BY exam_period";
    $stmt = $db->prepare($query);
    $stmt->execute([$department]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($schedules)) {
        sendJsonResponse(false, null, 'No exam schedule found for your department today');
    }

    // Use the first schedule found (could be enhanced to check current time)
    $currentSchedule = $schedules[0];
    $hallName = $currentSchedule['hall_name'];
    $departmentsInHall = json_decode($currentSchedule['departments'], true);
    $numDepartments = count($departmentsInHall);

    // Begin transaction
    $db->beginTransaction();

    // Get hall details
    $query = "SELECT * FROM halls WHERE name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName]);
    $hall = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hall) {
        $db->rollBack();
        sendJsonResponse(false, null, 'Exam hall not found');
    }

    // Get existing allocations for this hall and schedule
    $query = "SELECT seat_number, department FROM student_allocations 
              WHERE hall_name = ? AND exam_date = ? AND exam_period = ? 
              ORDER BY seat_number";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName, $currentSchedule['exam_date'], $currentSchedule['exam_period']]);
    $existingAllocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create seat map
    $seatMap = [];
    $departmentCounts = [];
    foreach ($existingAllocations as $alloc) {
        $seatMap[$alloc['seat_number']] = $alloc['department'];
        if (!isset($departmentCounts[$alloc['department']])) {
            $departmentCounts[$alloc['department']] = 0;
        }
        $departmentCounts[$alloc['department']]++;
    }

    // Calculate seat number based on number of departments
    $seatNumber = calculateSeatNumber($department, $departmentsInHall, $seatMap, $hall['total_seats'], $departmentCounts);

    if ($seatNumber === null) {
        $db->rollBack();
        sendJsonResponse(false, null, 'No suitable seat available in the assigned hall');
    }

    // Create allocation
    $query = "INSERT INTO student_allocations (matric_number, department, hall_name, venue, seat_number, exam_period, exam_date, schedule_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        $matricNumber, 
        $department, 
        $hallName, 
        $hall['venue'], 
        $seatNumber,
        $currentSchedule['exam_period'],
        $currentSchedule['exam_date'],
        $currentSchedule['id']
    ]);

    if (!$success) {
        $db->rollBack();
        sendJsonResponse(false, null, 'Failed to create allocation');
    }

    // Update hall count
    $query = "UPDATE halls SET occupied_seats = occupied_seats + 1 WHERE name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName]);

    // Get the created allocation
    $query = "SELECT * FROM student_allocations WHERE matric_number = ? AND exam_date = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$matricNumber, $currentSchedule['exam_date']]);
    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);

    $db->commit();
    sendJsonResponse(true, $allocation, null);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Allocation error: ' . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to allocate seat: ' . $e->getMessage());
}

/**
 * Calculate seat number based on number of departments and seating pattern
 */
function calculateSeatNumber($department, $departmentsInHall, $seatMap, $totalSeats, $departmentCounts) {
    $numDepartments = count($departmentsInHall);
    $deptIndex = array_search($department, $departmentsInHall);
    
    if ($deptIndex === false) {
        return null;
    }

    // Different seating patterns based on number of departments
    if ($numDepartments == 1) {
        // Pattern: 1-0-1 (one student, one empty seat)
        return findSeatOneDepartment($deptIndex, $seatMap, $totalSeats, $departmentCounts[$department] ?? 0);
    } 
    elseif ($numDepartments == 2) {
        // Pattern: alternating departments with empty seats
        return findSeatTwoDepartments($deptIndex, $departmentsInHall, $seatMap, $totalSeats, $departmentCounts);
    }
    else {
        // Pattern: 3+ departments - one student per seat
        return findSeatMultipleDepartments($deptIndex, $departmentsInHall, $seatMap, $totalSeats, $departmentCounts);
    }
}

/**
 * Seating pattern for 1 department: 1-0-1
 */
function findSeatOneDepartment($deptIndex, $seatMap, $totalSeats, $deptCount) {
    // For 1 department, use every other seat: 1, 3, 5, 7...
    $attempt = 0;
    while ($attempt < 100) {
        $seatNumber = 1 + ($deptCount + $attempt) * 2;
        
        if ($seatNumber > $totalSeats) {
            return null; // No more seats available
        }
        
        if (!isset($seatMap[$seatNumber])) {
            return $seatNumber;
        }
        
        $attempt++;
    }
    return null;
}

/**
 * Seating pattern for 2 departments: alternating with gaps
 */
function findSeatTwoDepartments($deptIndex, $departments, $seatMap, $totalSeats, $departmentCounts) {
    $deptCount = $departmentCounts[$departments[$deptIndex]] ?? 0;
    $otherDeptIndex = $deptIndex == 0 ? 1 : 0;
    $otherDeptCount = $departmentCounts[$departments[$otherDeptIndex]] ?? 0;
    
    // Pattern: DeptA - Empty - DeptB - Empty - DeptA - Empty - DeptB...
    $baseSeat = $deptIndex * 2 + 1; // DeptA starts at 1, DeptB at 3
    $attempt = 0;
    
    while ($attempt < 100) {
        $seatNumber = $baseSeat + ($attempt * 4);
        
        if ($seatNumber > $totalSeats) {
            return null;
        }
        
        if (!isset($seatMap[$seatNumber])) {
            return $seatNumber;
        }
        
        $attempt++;
    }
    return null;
}

/**
 * Seating pattern for 3+ departments: sequential allocation
 */
function findSeatMultipleDepartments($deptIndex, $departments, $seatMap, $totalSeats, $departmentCounts) {
    $deptCount = $departmentCounts[$departments[$deptIndex]] ?? 0;
    
    // For 3+ departments, use sequential allocation: Dept1, Dept2, Dept3, Dept1, Dept2, Dept3...
    $attempt = 0;
    while ($attempt < 100) {
        $seatNumber = $deptIndex + 1 + ($deptCount + $attempt) * count($departments);
        
        if ($seatNumber > $totalSeats) {
            return null;
        }
        
        if (!isset($seatMap[$seatNumber])) {
            return $seatNumber;
        }
        
        $attempt++;
    }
    return null;
}
?>