<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require admin authentication
$session = requireAuth($sessionManager, 'admin');
$admin = $session['data'];

$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_schedule':
                addExamSchedule($db);
                break;
            case 'edit_schedule':
                editExamSchedule($db);
                break;
            case 'delete_schedule':
                // Use the function from functions.php
                $scheduleId = intval($_POST['schedule_id'] ?? 0);
                if ($scheduleId > 0) {
                    $result = deleteExamSchedule($db, $scheduleId);
                    if ($result) {
                        $_SESSION['success'] = 'Exam schedule and associated allocations deleted successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to delete schedule';
                    }
                } else {
                    $_SESSION['error'] = 'Invalid schedule ID';
                }
                header('Location: manage-schedules.php');
                exit;
                break;
        }
    }
}

// Get all exam schedules
$query = "SELECT es.*, h.total_seats, h.occupied_seats 
          FROM exam_schedules es 
          JOIN halls h ON es.hall_name = h.name 
          ORDER BY es.exam_date DESC, es.exam_period";
$stmt = $db->prepare($query);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all halls
$query = "SELECT * FROM halls ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$halls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all departments
$allDepartments = getDepartments();
$allLevels = getLevels();

function addExamSchedule($db) {
    global $allDepartments;
    
    $hallName = trim($_POST['hall_name'] ?? '');
    $examPeriod = trim($_POST['exam_period'] ?? '');
    $examDate = trim($_POST['exam_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $departments = $_POST['departments'] ?? [];
    $departmentLevels = [];

    // Collect department levels
    if (isset($_POST['levels']) && is_array($_POST['levels'])) {
        foreach ($_POST['levels'] as $dept => $deptLevels) {
            if (is_array($deptLevels) && !empty($deptLevels)) {
                $departmentLevels[$dept] = $deptLevels;
            }
        }
    }

    // If no levels selected for any department, set empty array
    if (empty($departmentLevels)) {
        foreach ($departments as $dept) {
            $departmentLevels[$dept] = []; // Empty means all levels
        }
    }

    // DEBUG: Check what we're receiving
    error_log("DEBUG - Departments: " . print_r($departments, true));
    error_log("DEBUG - Department Levels: " . print_r($departmentLevels, true));

    if (empty($hallName) || empty($examPeriod) || empty($examDate) || empty($startTime) || empty($endTime) || empty($departments)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: manage-schedules.php');
        exit;
    }

    // Validate time
    if (strtotime($startTime) >= strtotime($endTime)) {
        $_SESSION['error'] = 'End time must be after start time';
        header('Location: manage-schedules.php');
        exit;
    }

    // Check for schedule conflict
    $query = "SELECT id FROM exam_schedules 
              WHERE hall_name = ? AND exam_date = ? AND exam_period = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName, $examDate, $examPeriod]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Schedule conflict: Hall already booked for this period';
        header('Location: manage-schedules.php');
        exit;
    }

    try {
        $query = "INSERT INTO exam_schedules (hall_name, exam_period, exam_date, start_time, end_time, departments, department_levels) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $hallName, 
            $examPeriod, 
            $examDate, 
            $startTime, 
            $endTime, 
            json_encode($departments),
            json_encode($departmentLevels)
        ]);
        
        $_SESSION['success'] = 'Exam schedule added successfully';
        header('Location: manage-schedules.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to add schedule: ' . $e->getMessage();
        header('Location: manage-schedules.php');
        exit;
    }
}

function editExamSchedule($db) {
    global $allDepartments;
    
    $scheduleId = intval($_POST['schedule_id'] ?? 0);
    $hallName = trim($_POST['hall_name'] ?? '');
    $examPeriod = trim($_POST['exam_period'] ?? '');
    $examDate = trim($_POST['exam_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $departments = $_POST['departments'] ?? [];
    $departmentLevels = [];

    // DEBUG: Check what we're receiving
    error_log("EDIT DEBUG - Schedule ID: " . $scheduleId);
    error_log("EDIT DEBUG - Departments: " . print_r($departments, true));
    error_log("EDIT DEBUG - POST data: " . print_r($_POST, true));

    // Collect department levels
    if (isset($_POST['levels']) && is_array($_POST['levels'])) {
        foreach ($_POST['levels'] as $dept => $deptLevels) {
            if (is_array($deptLevels) && !empty($deptLevels)) {
                $departmentLevels[$dept] = $deptLevels;
            }
        }
    }

    // If no levels selected for any department, set empty array
    if (empty($departmentLevels)) {
        foreach ($departments as $dept) {
            $departmentLevels[$dept] = []; // Empty means all levels
        }
    }

    // Basic validation only
    if ($scheduleId <= 0) {
        $_SESSION['error'] = 'Invalid schedule ID';
        header('Location: manage-schedules.php');
        exit;
    }

    if (empty($hallName) || empty($examPeriod) || empty($examDate) || empty($startTime) || empty($endTime)) {
        $_SESSION['error'] = 'All basic fields are required';
        header('Location: manage-schedules.php');
        exit;
    }

    // Check if departments array is empty
    if (empty($departments)) {
        $_SESSION['error'] = 'Please select at least one department';
        header('Location: manage-schedules.php');
        exit;
    }

    // Validate time
    if (strtotime($startTime) >= strtotime($endTime)) {
        $_SESSION['error'] = 'End time must be after start time';
        header('Location: manage-schedules.php');
        exit;
    }

    // Check for schedule conflict (excluding current schedule)
    $query = "SELECT id FROM exam_schedules 
              WHERE hall_name = ? AND exam_date = ? AND exam_period = ? AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$hallName, $examDate, $examPeriod, $scheduleId]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Schedule conflict: Hall already booked for this period';
        header('Location: manage-schedules.php');
        exit;
    }

    try {
        // Get the original schedule data to compare changes
        $originalQuery = "SELECT departments, department_levels, hall_name FROM exam_schedules WHERE id = ?";
        $originalStmt = $db->prepare($originalQuery);
        $originalStmt->execute([$scheduleId]);
        $originalData = $originalStmt->fetch(PDO::FETCH_ASSOC);
        
        $originalDepartments = json_decode($originalData['departments'] ?? '[]', true);
        $originalLevels = json_decode($originalData['department_levels'] ?? '{}', true);
        $originalHall = $originalData['hall_name'];
        
        // Find removed departments and changed levels
        $removedDepartments = array_diff($originalDepartments, $departments);
        $changedLevels = [];
        
        // Check for level changes in remaining departments
        foreach ($originalDepartments as $dept) {
            if (in_array($dept, $departments)) {
                $originalDeptLevels = $originalLevels[$dept] ?? [];
                $newDeptLevels = $departmentLevels[$dept] ?? [];
                
                // If levels were changed, find removed levels
                $removedLevels = array_diff($originalDeptLevels, $newDeptLevels);
                if (!empty($removedLevels)) {
                    $changedLevels[$dept] = $removedLevels;
                }
            }
        }
        
        // Remove allocations for departments that were removed or have level changes
        if (!empty($removedDepartments) || !empty($changedLevels)) {
            removeAllocationsForDepartments($db, $scheduleId, $removedDepartments, $changedLevels);
        }
        
        // If hall changed, remove all allocations (since students move to different hall)
        if ($originalHall !== $hallName) {
            // Hall changed - remove all allocations
            removeAllocationsForSchedule($db, $scheduleId);
        }

        // Update the schedule
        $query = "UPDATE exam_schedules 
                  SET hall_name = ?, exam_period = ?, exam_date = ?, start_time = ?, end_time = ?, departments = ?, department_levels = ?
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $hallName, 
            $examPeriod, 
            $examDate, 
            $startTime, 
            $endTime, 
            json_encode($departments),
            json_encode($departmentLevels),
            $scheduleId
        ]);
        
        $_SESSION['success'] = 'Exam schedule updated successfully';
        
        // Add info message if allocations were removed
        if (!empty($removedDepartments) || !empty($changedLevels) || ($originalHall !== $hallName)) {
            $_SESSION['info'] = 'Student allocations have been updated. Removed students from changed departments/levels.';
        }
        
        header('Location: manage-schedules.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to update schedule: ' . $e->getMessage();
        header('Location: manage-schedules.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exam Schedules - ExamSeat Pro</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <!-- Header -->
    <header style="background: white;">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manage Exam Schedules</h1>
                    <p class="text-gray-600">Schedule exams and assign departments to halls</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
                    <button class="btn btn-primary modal-trigger" data-modal="addScheduleModal">
                        ➕ Add Schedule
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-8">
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success mb-6">
                <span class="icon">✅</span>
                <div><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error mb-6">
                <span class="icon">❌</span>
                <div><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info mb-6">
                <span class="icon">ℹ️</span>
                <div><?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?></div>
            </div>
        <?php endif; ?>

        <!-- Schedules List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Exam Schedules</h3>
                <p class="card-description">Manage all exam schedules and department assignments</p>
            </div>
            <div class="card-content">
                <?php if (empty($schedules)): ?>
                    <div class="text-center py-8 text-gray-500">
                        No exam schedules found. Click "Add Schedule" to create one.
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($schedules as $schedule): 
                            $scheduleDepartments = json_decode($schedule['departments'], true) ?? [];
                            $departmentLevels = json_decode($schedule['department_levels'] ?? '{}', true);
                            $numDepartments = count($scheduleDepartments);
                        ?>
                            <div class="border rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h4 class="font-semibold text-lg flex items-center space-x-2">
                                            <span><?php echo htmlspecialchars($schedule['hall_name']); ?></span>
                                            <span class="badge badge-<?php echo $schedule['exam_period'] === 'morning' ? 'primary' : ($schedule['exam_period'] === 'afternoon' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($schedule['exam_period']); ?> Session
                                            </span>
                                        </h4>
                                        <p class="text-gray-600">
                                            <?php echo date('F j, Y', strtotime($schedule['exam_date'])); ?> • 
                                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button class="btn btn-outline btn-sm modal-trigger" 
                                                data-modal="editScheduleModal" 
                                                data-schedule='<?php echo htmlspecialchars(json_encode($schedule)); ?>'>
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this schedule? This will also remove any student allocations for this schedule.');">
                                            <input type="hidden" name="action" value="delete_schedule">
                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm btn-danger">🗑️ Delete</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Departments & Levels (<?php echo $numDepartments; ?>)</p>
                                        <div class="space-y-2 mt-2">
                                            <?php foreach ($scheduleDepartments as $dept): 
                                                $levels = $departmentLevels[$dept] ?? [];
                                            ?>
                                                <div class="flex items-center space-x-2">
                                                    <span class="badge badge-outline" style="background-color: <?php echo getDepartmentColor($dept); ?>20; color: <?php echo getDepartmentColor($dept); ?>; border-color: <?php echo getDepartmentColor($dept); ?>;">
                                                        <?php echo htmlspecialchars($dept); ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        <?php echo !empty($levels) ? implode(', ', $levels) : 'All Levels'; ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Seating Pattern</p>
                                        <p class="font-medium">
                                            <?php if ($numDepartments == 1): ?>
                                                1-0-1 Pattern (one student per seat with empty seat between)
                                            <?php elseif ($numDepartments == 2): ?>
                                                Alternating with empty seats
                                            <?php else: ?>
                                                Sequential allocation (one student per seat)
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600 mb-2">Hall Capacity</p>
                                    <div class="flex items-center justify-between">
                                        <span><?php echo $schedule['occupied_seats']; ?> / <?php echo $schedule['total_seats']; ?> seats occupied</span>
                                        <div class="w-32 bg-gray-200 rounded-full h-2">
                                            <div class="bg-emerald-600 h-2 rounded-full" style="width: <?php echo ($schedule['occupied_seats'] / $schedule['total_seats']) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Schedule Modal -->
    <div id="addScheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Exam Schedule</h3>
                <p class="modal-description">Schedule an exam period and assign departments to a hall</p>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_schedule">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="hall_name" class="form-label">Hall</label>
                        <select id="hall_name" name="hall_name" class="form-input" required>
                            <option value="">Select Hall</option>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?php echo htmlspecialchars($hall['name']); ?>">
                                    <?php echo htmlspecialchars($hall['name']); ?> (<?php echo $hall['total_seats']; ?> seats)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exam_period" class="form-label">Exam Period</label>
                        <select id="exam_period" name="exam_period" class="form-input" required>
                            <option value="">Select Period</option>
                            <option value="morning">Morning (9:00 AM - 12:00 PM)</option>
                            <option value="afternoon">Afternoon (2:00 PM - 5:00 PM)</option>
                            <option value="evening">Evening (6:00 PM - 9:00 PM)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="exam_date" class="form-label">Exam Date</label>
                        <input type="date" id="exam_date" name="exam_date" class="form-input" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="time" id="start_time" name="start_time" class="form-input" required>
                            <input type="time" id="end_time" name="end_time" class="form-input" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Departments & Levels</label>
                    <div class="department-levels-container space-y-3 max-h-64 overflow-y-auto p-3 border rounded">
                        <?php foreach ($allDepartments as $dept): ?>
                            <div class="department-level-group">
                                <label class="flex items-center space-x-2 mb-2">
                                    <input type="checkbox" name="departments[]" value="<?php echo htmlspecialchars($dept); ?>" class="form-checkbox dept-checkbox">
                                    <span class="font-medium"><?php echo htmlspecialchars($dept); ?></span>
                                </label>
                                <div class="level-checkboxes grid grid-cols-4 gap-2 ml-6 hidden">
                                    <?php foreach ($allLevels as $level): ?>
                                        <label class="flex items-center space-x-1 text-sm">
                                            <input type="checkbox" name="levels[<?php echo htmlspecialchars($dept); ?>][]" value="<?php echo htmlspecialchars($level); ?>" class="form-checkbox level-checkbox">
                                            <span><?php echo htmlspecialchars($level); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Select departments and their levels. If no levels selected, all levels will be included.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Exam Schedule</h3>
                <p class="modal-description">Update schedule and department assignments</p>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_schedule">
                <input type="hidden" id="edit_schedule_id" name="schedule_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="edit_hall_name" class="form-label">Hall</label>
                        <select id="edit_hall_name" name="hall_name" class="form-input" required>
                            <option value="">Select Hall</option>
                            <?php foreach ($halls as $hall): ?>
                                <option value="<?php echo htmlspecialchars($hall['name']); ?>">
                                    <?php echo htmlspecialchars($hall['name']); ?> (<?php echo $hall['total_seats']; ?> seats)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_exam_period" class="form-label">Exam Period</label>
                        <select id="edit_exam_period" name="exam_period" class="form-input" required>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="evening">Evening</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="edit_exam_date" class="form-label">Exam Date</label>
                        <input type="date" id="edit_exam_date" name="exam_date" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="time" id="edit_start_time" name="start_time" class="form-input" required>
                            <input type="time" id="edit_end_time" name="end_time" class="form-input" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Departments & Levels</label>
                    <div class="department-levels-container space-y-3 max-h-64 overflow-y-auto p-3 border rounded" id="edit_departments_container">
                        <?php foreach ($allDepartments as $dept): ?>
                            <div class="department-level-group">
                                <label class="flex items-center space-x-2 mb-2">
                                    <input type="checkbox" name="departments[]" value="<?php echo htmlspecialchars($dept); ?>" class="form-checkbox edit-dept-checkbox">
                                    <span class="font-medium"><?php echo htmlspecialchars($dept); ?></span>
                                </label>
                                <div class="level-checkboxes grid grid-cols-4 gap-2 ml-6 hidden">
                                    <?php foreach ($allLevels as $level): ?>
                                        <label class="flex items-center space-x-1 text-sm">
                                            <input type="checkbox" name="levels[<?php echo htmlspecialchars($dept); ?>][]" value="<?php echo htmlspecialchars($level); ?>" class="form-checkbox edit-level-checkbox">
                                            <span><?php echo htmlspecialchars($level); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Select departments and their levels. If no levels selected, all levels will be included.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
    <script>
        // Handle department checkbox changes to show/hide levels
        document.addEventListener('DOMContentLoaded', function() {
            // For add modal - show levels but don't auto-check them
            document.querySelectorAll('.dept-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const levelContainer = this.closest('.department-level-group').querySelector('.level-checkboxes');
                    if (this.checked) {
                        levelContainer.classList.remove('hidden');
                        // DON'T auto-check levels - let user choose manually
                    } else {
                        levelContainer.classList.add('hidden');
                        // Uncheck all level checkboxes when department is unchecked
                        levelContainer.querySelectorAll('.level-checkbox').forEach(function(levelCb) {
                            levelCb.checked = false;
                        });
                    }
                });
            });

            // For edit modal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-trigger') && e.target.dataset.modal === 'editScheduleModal') {
                    const schedule = JSON.parse(e.target.dataset.schedule);
                    populateEditForm(schedule);
                }
            });

            function populateEditForm(schedule) {
                // Fill basic form fields
                document.getElementById('edit_schedule_id').value = schedule.id;
                document.getElementById('edit_hall_name').value = schedule.hall_name;
                document.getElementById('edit_exam_period').value = schedule.exam_period;
                document.getElementById('edit_exam_date').value = schedule.exam_date;
                document.getElementById('edit_start_time').value = schedule.start_time;
                document.getElementById('edit_end_time').value = schedule.end_time;
                
                // Clear all department and level checkboxes first
                document.querySelectorAll('.edit-dept-checkbox').forEach(function(cb) {
                    cb.checked = false;
                    const levelContainer = cb.closest('.department-level-group').querySelector('.level-checkboxes');
                    levelContainer.classList.add('hidden');
                    levelContainer.querySelectorAll('.edit-level-checkbox').forEach(function(levelCb) {
                        levelCb.checked = false;
                    });
                });
                
                // Check the departments and levels from the schedule
                const departments = JSON.parse(schedule.departments || '[]');
                const departmentLevels = JSON.parse(schedule.department_levels || '{}');
                
                departments.forEach(function(dept) {
                    const checkbox = document.querySelector('.edit-dept-checkbox[value="' + dept + '"]');
                    if (checkbox) {
                        checkbox.checked = true;
                        const levelContainer = checkbox.closest('.department-level-group').querySelector('.level-checkboxes');
                        levelContainer.classList.remove('hidden');
                        
                        // Check the levels for this department
                        const levels = departmentLevels[dept] || [];
                        
                        levels.forEach(function(level) {
                            const levelCheckbox = levelContainer.querySelector('.edit-level-checkbox[value="' + level + '"]');
                            if (levelCheckbox) {
                                levelCheckbox.checked = true;
                            }
                        });
                    }
                });
            }

            // Handle edit department checkbox changes - show levels but don't auto-check
            document.querySelectorAll('.edit-dept-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const levelContainer = this.closest('.department-level-group').querySelector('.level-checkboxes');
                    if (this.checked) {
                        levelContainer.classList.remove('hidden');
                        // DON'T auto-check levels - let user choose manually
                    } else {
                        levelContainer.classList.add('hidden');
                        levelContainer.querySelectorAll('.edit-level-checkbox').forEach(function(levelCb) {
                            levelCb.checked = false;
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>