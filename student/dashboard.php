<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require student authentication
$session = requireAuth($sessionManager, 'student');
$student = $session['data'];

// Check for existing allocation for today
$allocation = null;
$db = $database->getConnection();
$query = "SELECT sa.*, es.start_time, es.end_time 
          FROM student_allocations sa 
          LEFT JOIN exam_schedules es ON sa.schedule_id = es.id 
          WHERE sa.matric_number = ? AND sa.exam_date = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute([$student['matricNumber']]);
if ($stmt->rowCount() > 0) {
    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get today's exam schedule for student's department
$query = "SELECT * FROM exam_schedules 
          WHERE exam_date = CURDATE()
          AND JSON_CONTAINS(departments, JSON_QUOTE(:dept))
          AND JSON_CONTAINS(department_levels, JSON_QUOTE(:level), CONCAT('$.', JSON_QUOTE(:dept)))
          ORDER BY exam_period";
$stmt = $db->prepare($query);
$stmt->execute([
    ':dept' => $student['department'],
    ':level' => $student['level'],
]);
$todaySchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get department info
$department = $student['department'];
$allDepartments = getDepartments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ExamSeat Pro</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Student Dashboard</h1>
                    <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($student['fullName']); ?></p>
                </div>
                <a href="#" onclick="confirmLogout(event)" class="btn btn-outline btn-logout" data-user-type="student">
                    🚪 Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container py-8">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Student Info Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title flex items-center space-x-2">
                        <span>👥</span>
                        <span>Student Information</span>
                    </h3>
                </div>
                <div class="card-content">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Full Name</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($student['fullName']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Matric Number</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($student['matricNumber']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Department</p>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($student['department']); ?></span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Level</p>
                            <span class="badge badge-outline"><?php echo htmlspecialchars($student['level']); ?> Level</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Exam Schedule -->
            <?php if (!empty($todaySchedules)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title flex items-center space-x-2">
                        <span>📅</span>
                        <span>Today's Exam Schedule</span>
                    </h3>
                </div>
                <div class="card-content">
                    <div class="space-y-4">
                        <?php foreach ($todaySchedules as $schedule): 
                            $departmentsInHall = json_decode($schedule['departments'], true);
                            $numDepartments = count($departmentsInHall);
                        ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($schedule['hall_name']); ?></h4>
                                        <p class="text-gray-600">
                                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                        </p>
                                    </div>
                                    <span class="badge badge-<?php echo $schedule['exam_period'] === 'morning' ? 'primary' : ($schedule['exam_period'] === 'afternoon' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($schedule['exam_period']); ?> Session
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Departments (<?php echo $numDepartments; ?>)</p>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <?php foreach ($departmentsInHall as $dept): ?>
                                                <span class="badge badge-outline badge-sm <?php echo $dept === $department ? 'badge-primary' : ''; ?>">
                                                    <?php echo htmlspecialchars($dept); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Seating Pattern</p>
                                        <p class="text-sm font-medium">
                                            <?php if ($numDepartments == 1): ?>
                                                1-0-1 Pattern
                                            <?php elseif ($numDepartments == 2): ?>
                                                Alternating with empty seats
                                            <?php else: ?>
                                                Sequential allocation
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Seat Allocation Card -->
            <?php if (!$allocation): ?>
                <div class="card" id="seat-allocation-form">
                    <div class="card-header">
                        <h3 class="card-title">Exam Seat Allocation</h3>
                        <p class="card-description">Get your seat assignment for today's exam</p>
                    </div>
                    <div class="card-content space-y-4">
                        <?php if (empty($todaySchedules)): ?>
                            <div class="alert alert-warning">
                                <span class="icon">⚠️</span>
                                <div>No exam schedule found for your department today.</div>
                            </div>
                        <?php else: ?>
                            <form action="/exam-seat-allocation/api/allocate-seat.php" method="POST" class="ajax-form seat-allocation-form">
                                <input type="hidden" name="matric_number" value="<?php echo htmlspecialchars($student['matricNumber']); ?>">
                                
                                <button type="submit" class="btn btn-primary w-full btn-lg">
                                    Get My Seat Assignment
                                </button>
                            </form>

                            <div class="p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-semibold text-gray-900 mb-2">Seating Information:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Seats are allocated based on smart patterns</li>
                                    <li>• Ensures proper spacing between students</li>
                                    <li>• Your assignment is specific to today's exam</li>
                                    <li>• Bring this allocation to your exam venue</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card" style="border-top: 4px solid #059669;">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                            <span class="text-emerald-600">✅</span>
                        </div>
                        <h3 class="card-title text-emerald-600">Seat Allocated Successfully!</h3>
                        <p class="card-description">Your exam seat assignment is ready for today.</p>
                    </div>
                    <div class="card-content space-y-6">
                        <div class="grid gap-4">
                            <div class="flex justify-between items-center p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                                <span class="font-medium text-emerald-700">Seat Number:</span>
                                <div class="text-2xl font-bold text-emerald-600">#<?php echo $allocation['seat_number']; ?></div>
                            </div>

                            <div class="p-4 bg-teal-50 rounded-lg border border-teal-200">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="icon text-teal-600">📍</span>
                                    <span class="font-medium text-teal-700">Examination Venue</span>
                                </div>
                                <div class="text-lg font-semibold text-teal-900"><?php echo htmlspecialchars($allocation['hall_name']); ?></div>
                                <div class="text-teal-700"><?php echo htmlspecialchars($allocation['venue']); ?></div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="p-4 bg-blue-50 rounded-lg">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="icon text-blue-600">🕒</span>
                                        <span class="font-medium text-blue-700">Exam Time</span>
                                    </div>
                                    <div class="text-blue-900">
                                        <?php if ($allocation['start_time']): ?>
                                            <?php echo date('g:i A', strtotime($allocation['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($allocation['end_time'])); ?>
                                        <?php else: ?>
                                            <?php echo ucfirst($allocation['exam_period']); ?> Session
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="p-4 bg-purple-50 rounded-lg">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="icon text-purple-600">📅</span>
                                        <span class="font-medium text-purple-700">Exam Date</span>
                                    </div>
                                    <div class="text-purple-900"><?php echo date('F j, Y', strtotime($allocation['exam_date'])); ?></div>
                                </div>

                                <div class="p-4 bg-orange-50 rounded-lg">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="icon text-orange-600">🎯</span>
                                        <span class="font-medium text-orange-700">Session</span>
                                    </div>
                                    <div class="text-orange-900"><?php echo ucfirst($allocation['exam_period']); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <span class="icon">ℹ</span>
                            <div>
                                <strong>Important:</strong> 
                                Please arrive at the examination venue 30 minutes before the scheduled time. 
                                Bring this seat allocation details and your student ID card.
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline w-full btn-print" onclick="window.print()">
                            Print Seat Details
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div id="seat-allocation-result"></div>
        </div>
    </main>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
    <script>
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = '/exam-seat-allocation/auth/student/logout.php';
            }
        }
    </script>
</body>
</html>