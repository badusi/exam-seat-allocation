<?php
function extractDepartment($matricNumber) {
    // Clean and uppercase the matric number
    $matricNumber = strtoupper(trim($matricNumber));
    
    // Log original matric number
    error_log("Extracting department from: " . $matricNumber);
    
    // Extract first 2 letters ONLY
    $deptCode = substr($matricNumber, 0, 2);
    error_log("Extracted department code: " . $deptCode);

    $departmentMap = [
        'AC' => 'Accountancy',
        'AG' => 'Agricultural Technology',
        'BA' => 'Business Administration and Management',
        'CE' => 'Computer Engineering',
        'CS' => 'Computer Science',
        'EE' => 'Electrical and Electronics Engineering Technology',
        'EM' => 'Estate Management',
        'OT' => 'Office Technology and Management',  
        'PA' => 'Public Administration', 
        'SL' => 'Science Laboratory Technology', 
        'ST' => 'Statistics', 
        'TL' => 'Tourism and Leisure Management',
        'UR' => 'Urban and Regional Planning', 
    ];

    $fullDepartment = $departmentMap[$deptCode] ?? 'Unknown Department';
    error_log("Final department name: " . $fullDepartment);
    return $fullDepartment;
}

function sendJsonResponse($success, $data = null, $error = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

function requireAuth($sessionManager, $userType = null) {
    $session = $sessionManager->getSession();
    if (!$session) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            sendJsonResponse(false, null, 'Authentication required');
        } else {
            header('Location: /exam-seat-allocation/auth/' . ($userType ?: 'student') . '/login.php');
            exit;
        }
    }
    
    if ($userType && $session['user_type'] !== $userType) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            sendJsonResponse(false, null, 'Insufficient permissions');
        } else {
            header('Location: /exam-seat-allocation/auth/' . $userType . '/login.php');
            exit;
        }
    }
    
    return $session;
}

function getLevels() {
    return ['ND 1', 'ND 2', 'HND 1', 'HND 2'];
}

function getDepartmentLevels($department, $selectedLevels = []) {
    // If specific levels are provided, return only those
    if (!empty($selectedLevels)) {
        return array_intersect(getLevels(), $selectedLevels);
    }
    
    // Otherwise return all levels
    return getLevels();
}

function getDepartments() {
    return [
        'Accountancy',
        'Agricultural Technology', 
        'Business Administration and Management',
        'Computer Engineering',
        'Computer Science',
        'Electrical and Electronics Engineering Technology',
        'Estate Management',
        'Office Technology and Management',
        'Public Administration',
        'Science Laboratory Technology',
        'Statistics',
        'Tourism and Leisure Management',
        'Urban and Regional Planning'
    ];
}

// Student Management Functions
function getAllStudents($db, $filters = []) {
    $query = "SELECT * FROM students WHERE 1=1";
    $params = [];
    
    if (!empty($filters['department'])) {
        $query .= " AND department = ?";
        $params[] = $filters['department'];
    }
    
    if (!empty($filters['level'])) {
        $query .= " AND level = ?";
        $params[] = $filters['level'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND (matric_number LIKE ? OR full_name LIKE ? OR email LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentById($db, $id) {
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateStudent($db, $id, $data) {
    $query = "UPDATE students SET 
              matric_number = ?, 
              email = ?, 
              full_name = ?, 
              department = ?, 
              level = ?,
              updated_at = CURRENT_TIMESTAMP 
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $data['matric_number'],
        $data['email'],
        $data['full_name'],
        $data['department'],
        $data['level'],
        $id
    ]);
}

function deleteStudent($db, $id) {
    // First delete allocations for this student
    $allocQuery = "DELETE FROM student_allocations WHERE matric_number = (SELECT matric_number FROM students WHERE id = ?)";
    $allocStmt = $db->prepare($allocQuery);
    $allocStmt->execute([$id]);
    
    // Then delete the student
    $query = "DELETE FROM students WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$id]);
}

function studentExists($db, $matricNumber, $email = null) {
    $query = "SELECT COUNT(*) as count FROM students WHERE matric_number = ?";
    $params = [$matricNumber];
    
    if ($email) {
        $query .= " OR email = ?";
        $params[] = $email;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

// Schedule Management Functions
function saveExamSchedule($db, $hallName, $examPeriod, $examDate, $startTime, $endTime, $departmentLevels) {
    // Convert department levels to JSON format
    $departments = array_keys($departmentLevels);
    $departmentLevelsJson = json_encode($departmentLevels);
    
    $query = "INSERT INTO exam_schedules 
              (hall_name, exam_period, exam_date, start_time, end_time, departments, department_levels) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $hallName,
        $examPeriod,
        $examDate,
        $startTime,
        $endTime,
        json_encode($departments),
        $departmentLevelsJson
    ]);
}

function updateExamSchedule($db, $id, $hallName, $examPeriod, $examDate, $startTime, $endTime, $departmentLevels) {
    // Convert department levels to JSON format
    $departments = array_keys($departmentLevels);
    $departmentLevelsJson = json_encode($departmentLevels);
    
    $query = "UPDATE exam_schedules SET 
              hall_name = ?, 
              exam_period = ?, 
              exam_date = ?, 
              start_time = ?, 
              end_time = ?, 
              departments = ?, 
              department_levels = ?,
              updated_at = CURRENT_TIMESTAMP 
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $hallName,
        $examPeriod,
        $examDate,
        $startTime,
        $endTime,
        json_encode($departments),
        $departmentLevelsJson,
        $id
    ]);
}

function getExamSchedule($db, $id) {
    $query = "SELECT * FROM exam_schedules WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($schedule) {
        // Parse JSON data
        $schedule['departments'] = json_decode($schedule['departments'], true) ?? [];
        $schedule['department_levels'] = json_decode($schedule['department_levels'], true) ?? [];
    }
    
    return $schedule;
}

function getAllExamSchedules($db) {
    $query = "SELECT * FROM exam_schedules ORDER BY exam_date DESC, exam_period DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON data for each schedule
    foreach ($schedules as &$schedule) {
        $schedule['departments'] = json_decode($schedule['departments'], true) ?? [];
        $schedule['department_levels'] = json_decode($schedule['department_levels'], true) ?? [];
    }
    
    return $schedules;
}

// Allocation Management Functions
/**
 * Remove all student allocations for a specific schedule
 */
function removeAllocationsForSchedule($db, $scheduleId) {
    try {
        // Get schedule details first
        $scheduleQuery = "SELECT hall_name FROM exam_schedules WHERE id = ?";
        $scheduleStmt = $db->prepare($scheduleQuery);
        $scheduleStmt->execute([$scheduleId]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) return false;

        // Delete all allocations for this schedule
        $deleteQuery = "DELETE FROM student_allocations WHERE schedule_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$scheduleId]);
        
        // Update hall occupied seats count
        updateHallOccupiedSeats($db, $schedule['hall_name']);
        
        return true;
    } catch (Exception $e) {
        error_log("Error removing allocations for schedule: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove allocations for specific departments/levels from a schedule
 */
function removeAllocationsForDepartments($db, $scheduleId, $removedDepartments = [], $changedLevels = []) {
    try {
        if (empty($removedDepartments) && empty($changedLevels)) {
            return true;
        }

        // Build the WHERE clause based on what needs to be removed
        $whereConditions = [];
        $params = [$scheduleId];

        if (!empty($removedDepartments)) {
            $placeholders = implode(',', array_fill(0, count($removedDepartments), '?'));
            $whereConditions[] = "department IN ($placeholders)";
            $params = array_merge($params, $removedDepartments);
        }

        if (!empty($changedLevels)) {
            foreach ($changedLevels as $dept => $levels) {
                if (empty($levels)) continue;
                
                $levelPlaceholders = implode(',', array_fill(0, count($levels), '?'));
                $whereConditions[] = "(department = ? AND level IN ($levelPlaceholders))";
                $params[] = $dept;
                $params = array_merge($params, $levels);
            }
        }

        $whereClause = implode(' OR ', $whereConditions);
        
        // Get hall name first for updating occupied seats
        $hallQuery = "SELECT hall_name FROM exam_schedules WHERE id = ?";
        $hallStmt = $db->prepare($hallQuery);
        $hallStmt->execute([$scheduleId]);
        $schedule = $hallStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) return false;

        // Delete the allocations
        $deleteQuery = "DELETE FROM student_allocations WHERE schedule_id = ? AND ($whereClause)";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute($params);
        
        // Update hall occupied seats
        updateHallOccupiedSeats($db, $schedule['hall_name']);
        
        return true;
    } catch (Exception $e) {
        error_log("Error removing department allocations: " . $e->getMessage());
        return false;
    }
}

/**
 * Update hall occupied seats count
 */
function updateHallOccupiedSeats($db, $hallName) {
    try {
        // Count current allocations for this hall
        $countQuery = "SELECT COUNT(*) as occupied_count FROM student_allocations WHERE hall_name = ?";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute([$hallName]);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update hall occupied seats
        $updateQuery = "UPDATE halls SET occupied_seats = ? WHERE name = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$result['occupied_count'], $hallName]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating hall occupied seats: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced delete exam schedule function with automatic cleanup
 */
function deleteExamSchedule($db, $scheduleId) {
    try {
        // First get schedule details before deleting
        $scheduleQuery = "SELECT hall_name FROM exam_schedules WHERE id = ?";
        $scheduleStmt = $db->prepare($scheduleQuery);
        $scheduleStmt->execute([$scheduleId]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            return false; // Schedule not found
        }

        // Remove all allocations for this schedule
        removeAllocationsForSchedule($db, $scheduleId);
        
        // Then delete the schedule
        $query = "DELETE FROM exam_schedules WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$scheduleId]);
        
        return true;
    } catch (Exception $e) {
        error_log("Delete schedule error: " . $e->getMessage());
        return false;
    }
}

function deleteExamScheduleOld($db, $id) {
    // First check if there are allocations for this schedule
    $query = "SELECT COUNT(*) as count FROM student_allocations WHERE schedule_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        return false; // Cannot delete schedule with existing allocations
    }
    
    // Delete the schedule
    $query = "DELETE FROM exam_schedules WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$id]);
}

// Hall Management Functions
function getAllHalls($db) {
    $query = "SELECT * FROM halls ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getHallByName($db, $hallName) {
    $query = "SELECT * FROM halls WHERE name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateHallSeats($db, $hallName, $occupiedSeats) {
    $query = "UPDATE halls SET occupied_seats = ?, updated_at = CURRENT_TIMESTAMP WHERE name = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$occupiedSeats, $hallName]);
}

// Allocation Functions
function getStudentAllocationDetails($db, $matricNumber) {
    $query = "SELECT sa.*, es.exam_date, es.start_time, es.end_time, es.exam_period
              FROM student_allocations sa
              LEFT JOIN exam_schedules es ON sa.schedule_id = es.id
              WHERE sa.matric_number = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$matricNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllocationsBySchedule($db, $scheduleId) {
    $query = "SELECT sa.*, s.full_name, s.email 
              FROM student_allocations sa
              JOIN students s ON sa.matric_number = s.matric_number
              WHERE sa.schedule_id = ?
              ORDER BY sa.seat_number ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$scheduleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function validateSeatingArrangement($hallName, $db) {
    $query = "SELECT seat_number, department FROM student_allocations WHERE hall_name = ? ORDER BY seat_number";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName]);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get hall info
    $query = "SELECT total_seats FROM halls WHERE name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName]);
    $hall = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hall) return true;
    
    $seatsPerRow = 10;
    $totalSeats = $hall['total_seats'];
    
    // Create seat map
    $seatMap = [];
    foreach ($allocations as $allocation) {
        $seatMap[$allocation['seat_number']] = $allocation['department'];
    }
    
    // Check each occupied seat
    foreach ($seatMap as $seatNumber => $department) {
        $adjacentSeats = getAdjacentSeatsForValidation($seatNumber, $seatsPerRow, $totalSeats);
        
        foreach ($adjacentSeats as $adjacentSeat) {
            if (isset($seatMap[$adjacentSeat]) && $seatMap[$adjacentSeat] === $department) {
                return false; // Found same department adjacent
            }
        }
    }
    
    return true; // No violations found
}

function getAdjacentSeatsForValidation($seatNumber, $seatsPerRow, $totalSeats) {
    $adjacent = [];
    
    // Calculate row and column (0-indexed)
    $row = floor(($seatNumber - 1) / $seatsPerRow);
    $col = ($seatNumber - 1) % $seatsPerRow;
    
    // Define all 8 possible adjacent positions (including diagonals)
    $directions = [
        [-1, -1], [-1, 0], [-1, 1],  // Top-left, Top, Top-right
        [0, -1],           [0, 1],   // Left, Right
        [1, -1],  [1, 0],  [1, 1]    // Bottom-left, Bottom, Bottom-right
    ];
    
    foreach ($directions as $direction) {
        $newRow = $row + $direction[0];
        $newCol = $col + $direction[1];
        
        // Check if the new position is valid
        if ($newRow >= 0 && $newCol >= 0 && $newCol < $seatsPerRow) {
            $newSeatNumber = ($newRow * $seatsPerRow) + $newCol + 1;
            
            // Check if seat number is within valid range
            if ($newSeatNumber >= 1 && $newSeatNumber <= $totalSeats) {
                $adjacent[] = $newSeatNumber;
            }
        }
    }
    
    return $adjacent;
}

function getDepartmentColor($department) {
    $colors = [
        'Accountancy' => '#ec4899',             // Pink
        'Agricultural Technology' => '#84cc16', // Lime
        'Business Administration and Management' => '#f97316', // Orange
        'Computer Engineering' => '#22c55e',    // Green
        'Computer Science' => '#3b82f6',      // Blue
        'Electrical and Electronics Engineering Technology' => '#ef4444',     // Red
        'Estate Management' => '#8b5cf6', // Violet
        'Office Technology and Management' => '#06b6d4',  // Cyan
        'Public Administration' => '#00ff15ff',     // Light Green
        'Science Laboratory Technology' => '#f59e0b', // Amber
        'Statistics' => '#10b981',            // Emerald
        'Tourism and Leisure Management' => '#6366f1',    // Indigo
        'Urban and Regional Planning'   => '#fffb00ff'   //yellow 
    ];
    
    return $colors[$department] ?? '#6b7280'; // Default gray
}

function getHallDistributionSummary($db) {
    $query = "SELECT 
                h.name as hall_name,
                h.venue,
                h.total_seats,
                h.occupied_seats,
                COUNT(DISTINCT sa.department) as department_count,
                GROUP_CONCAT(DISTINCT sa.department) as departments
              FROM halls h
              LEFT JOIN student_allocations sa ON h.name = sa.hall_name
              GROUP BY h.id, h.name, h.venue, h.total_seats, h.occupied_seats
              ORDER BY h.created_at";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = [];
    foreach ($results as $result) {
        $summary[] = [
            'hall_name' => $result['hall_name'],
            'venue' => $result['venue'],
            'total_seats' => (int)$result['total_seats'],
            'occupied_seats' => (int)$result['occupied_seats'],
            'available_seats' => (int)$result['total_seats'] - (int)$result['occupied_seats'],
            'department_count' => (int)$result['department_count'],
            'departments' => $result['departments'] ? explode(',', $result['departments']) : [],
            'occupancy_percentage' => $result['total_seats'] > 0 ? round(($result['occupied_seats'] / $result['total_seats']) * 100, 1) : 0,
            'is_valid' => validateSeatingArrangement($result['hall_name'], $db)
        ];
    }
    
    return $summary;
}

function departmentExistsInHall($hallName, $department, $db) {
    $query = "SELECT COUNT(*) as count FROM student_allocations WHERE hall_name = ? AND department = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName, $department]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

function getAvailableHallsForDepartment($department, $db) {
    $query = "SELECT h.* FROM halls h 
              WHERE h.occupied_seats < h.total_seats 
              AND h.name NOT IN (
                  SELECT DISTINCT hall_name 
                  FROM student_allocations 
                  WHERE department = ?
              )
              ORDER BY h.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$department]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepartmentStatistics($db) {
    $query = "SELECT 
                department,
                COUNT(*) as total_students,
                COUNT(DISTINCT hall_name) as halls_used,
                GROUP_CONCAT(DISTINCT hall_name) as hall_list
              FROM student_allocations 
              GROUP BY department 
              ORDER BY total_students DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [];
    foreach ($results as $result) {
        $stats[] = [
            'department' => $result['department'],
            'total_students' => (int)$result['total_students'],
            'halls_used' => (int)$result['halls_used'],
            'halls' => $result['hall_list'] ? explode(',', $result['hall_list']) : [],
            'average_per_hall' => $result['halls_used'] > 0 ? round($result['total_students'] / $result['halls_used'], 1) : 0
        ];
    }
    
    return $stats;
}

// Utility Functions
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

function formatTime($time, $format = 'g:i A') {
    return date($format, strtotime($time));
}

function isPastDate($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

function getExamPeriodDisplay($period) {
    $periods = [
        'morning' => 'Morning',
        'afternoon' => 'Afternoon', 
        'evening' => 'Evening'
    ];
    
    return $periods[$period] ?? $period;
}
?>