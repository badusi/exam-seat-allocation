<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Require admin authentication
$session = requireAuth($sessionManager, 'admin');
$admin = $session['data'];

// Get statistics
$db = $database->getConnection();

// Get halls
$hallsQuery = "SELECT * FROM halls ORDER BY created_at ASC";
$hallsStmt = $db->prepare($hallsQuery);
$hallsStmt->execute();
$halls = $hallsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get student allocations
$allocationsQuery = "SELECT * FROM student_allocations ORDER BY allocated_at DESC";
$allocationsStmt = $db->prepare($allocationsQuery);
$allocationsStmt->execute();
$allocations = $allocationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get exam schedules
$schedulesQuery = "SELECT COUNT(*) as total_schedules FROM exam_schedules";
$schedulesStmt = $db->prepare($schedulesQuery);
$schedulesStmt->execute();
$schedulesCount = $schedulesStmt->fetch(PDO::FETCH_ASSOC)['total_schedules'];

// Get today's schedules
$todaySchedulesQuery = "SELECT COUNT(*) as today_schedules FROM exam_schedules WHERE exam_date = CURDATE()";
$todayStmt = $db->prepare($todaySchedulesQuery);
$todayStmt->execute();
$todaySchedules = $todayStmt->fetch(PDO::FETCH_ASSOC)['today_schedules'];

// Calculate statistics
$totalSeats = array_sum(array_column($halls, 'total_seats'));
$occupiedSeats = array_sum(array_column($halls, 'occupied_seats'));
$availableSeats = $totalSeats - $occupiedSeats;

// Validate seating arrangements for all halls
$validationResults = [];
foreach ($halls as $hall) {
    $validationResults[$hall['name']] = validateSeatingArrangement($hall['name'], $db);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ExamSeat Pro</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <!-- Header -->
    <header style="background: white;">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                    <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($admin['fullName']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="btn btn-primary modal-trigger" data-modal="addHallModal">
                        ➕ Add Hall
                    </button>
                    <a href="manage-schedules.php" class="btn btn-outline">
                        📅 Manage Schedules
                    </a>
                    <a href="#" onclick="confirmLogout(event)" class="btn btn-outline btn-logout" data-user-type="admin">
                        🚪 Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-8">
        <!-- Student Search Section -->
        <div class="card mb-8">
            <div class="card-header">
                <h3 class="card-title">🔍 Student Search</h3>
                <p class="card-description">Search for students by name or matric number</p>
            </div>
            <div class="card-content">
                <form id="studentSearchForm" class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label for="search_type" class="form-label">Search By</label>
                            <select id="search_type" name="search_type" class="form-input">
                                <option value="matric">Matric Number</option>
                                <option value="name">Student Name</option>
                            </select>
                        </div>
                        <div class="form-group col-span-2">
                            <label for="search_term" class="form-label">Search Term</label>
                            <input type="text" id="search_term" name="search_term" class="form-input" placeholder="Enter matric number or student name">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Search Student</button>
                </form>
                
                <div id="searchResults" class="mt-4 hidden">
                    <!-- Search results will appear here -->
                </div>
            </div>
        </div>

        <!-- Validation Status -->
        <?php 
        $allValid = array_reduce($validationResults, function($carry, $item) { return $carry && $item; }, true);
        ?>
        <div class="validation-info <?php echo $allValid ? 'validation-success' : 'validation-error'; ?> mb-6">
            <div class="flex items-center space-x-2">
                <span class="icon"><?php echo $allValid ? '✅' : '⚠️'; ?></span>
                <strong>Systematic Allocation Status:</strong>
                <span><?php echo $allValid ? 'All seating arrangements follow systematic pattern' : 'Some violations detected in seating pattern'; ?></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid mb-8">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Halls</div>
                    <span class="icon text-gray-400">📍</span>
                </div>
                <div class="stat-value"><?php echo count($halls); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Seats</div>
                    <span class="icon text-gray-400">⚙️</span>
                </div>
                <div class="stat-value"><?php echo $totalSeats; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Occupied Seats</div>
                    <span class="icon text-gray-400">👥</span>
                </div>
                <div class="stat-value text-emerald-600"><?php echo $occupiedSeats; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Available Seats</div>
                    <span class="icon text-gray-400">👁️</span>
                </div>
                <div class="stat-value text-orange-600"><?php echo $availableSeats; ?></div>
            </div>

            <!-- New Stats Cards -->
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Exam Schedules</div>
                    <span class="icon text-gray-400">📅</span>
                </div>
                <div class="stat-value"><?php echo $schedulesCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Today's Exams</div>
                    <span class="icon text-gray-400">🎯</span>
                </div>
                <div class="stat-value text-blue-600"><?php echo $todaySchedules; ?></div>
            </div>
        </div>

        <!-- Main Content - TABS RESTORED -->
        <div class="tabs">
            <div class="tab-list">
                <button class="tab-trigger active" data-tab="halls">Examination Halls</button>
                <button class="tab-trigger" data-tab="students">Student Allocations</button>
                <button class="tab-trigger" data-tab="seating">Seating Arrangement</button>
            </div>

            <!-- Halls Tab -->
            <div class="tab-content active" data-tab-content="halls">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Examination Halls</h3>
                        <p class="card-description">Manage examination halls and their capacities</p>
                    </div>
                    <div class="card-content">
                        <?php if (empty($halls)): ?>
                            <div class="text-center py-8 text-gray-500">
                                No halls added yet. Click "Add Hall" to get started.
                            </div>
                        <?php else: ?>
                            <div class="grid gap-4">
                                <?php foreach ($halls as $hall): ?>
                                    <div class="flex items-center justify-between p-4 border rounded-lg">
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <h4 class="font-semibold text-lg"><?php echo htmlspecialchars($hall['name']); ?></h4>
                                                <?php if (isset($validationResults[$hall['name']])): ?>
                                                    <span class="icon" title="<?php echo $validationResults[$hall['name']] ? 'Valid systematic arrangement' : 'Pattern violations detected'; ?>">
                                                        <?php echo $validationResults[$hall['name']] ? '✅' : '⚠️'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($hall['venue']); ?></p>
                                            <div class="flex items-center space-x-4 mt-2">
                                                <span class="badge badge-outline">Total: <?php echo $hall['total_seats']; ?> seats</span>
                                                <span class="badge badge-primary">Occupied: <?php echo $hall['occupied_seats']; ?></span>
                                                <span class="badge badge-secondary">Available: <?php echo $hall['total_seats'] - $hall['occupied_seats']; ?></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-emerald-600">
                                                <?php echo $hall['total_seats'] > 0 ? round(($hall['occupied_seats'] / $hall['total_seats']) * 100) : 0; ?>%
                                            </div>
                                            <div class="text-sm text-gray-500">Occupied</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- Students Tab -->
            <div class="tab-content" data-tab-content="students">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Student Allocations</h3>
                        <p class="card-description">View all students with systematic seat assignments</p>
                    </div>
                    <div class="card-content">
                        <?php if (empty($allocations)): ?>
                            <div class="text-center py-8 text-gray-500">No students have been allocated seats yet.</div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Matric Number</th>
                                            <th>Department</th>
                                            <th>Hall</th>
                                            <th>Seat</th>
                                            <th>Exam Period</th>
                                            <th>Allocated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allocations as $allocation): ?>
                                            <tr>
                                                <td class="font-medium"><?php echo htmlspecialchars($allocation['matric_number']); ?></td>
                                                <td>
                                                    <span class="badge badge-outline" style="background-color: <?php echo getDepartmentColor($allocation['department']); ?>20; color: <?php echo getDepartmentColor($allocation['department']); ?>; border-color: <?php echo getDepartmentColor($allocation['department']); ?>;">
                                                        <?php echo htmlspecialchars($allocation['department']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($allocation['hall_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-primary">#<?php echo $allocation['seat_number']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $allocation['exam_period'] === 'morning' ? 'primary' : ($allocation['exam_period'] === 'afternoon' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($allocation['exam_period']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($allocation['allocated_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seating Tab -->
            <div class="tab-content" data-tab-content="seating">
                <?php foreach ($halls as $hall): ?>
                    <?php
                    // Get allocations for this hall
                    $hallAllocations = array_filter($allocations, function($a) use ($hall) {
                        return $a['hall_name'] === $hall['name'];
                    });
                    
                    // Create seat map
                    $seatMap = [];
                    foreach ($hallAllocations as $allocation) {
                        $seatMap[$allocation['seat_number']] = $allocation;
                    }
                    
                    $isValidArrangement = $validationResults[$hall['name']] ?? true;
                    ?>
                    <div class="card mb-6">
                        <div class="card-header">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="card-title flex items-center space-x-2">
                                        <span><?php echo htmlspecialchars($hall['name']); ?></span>
                                        <span class="icon" title="<?php echo $isValidArrangement ? 'Valid systematic pattern' : 'Pattern violations detected'; ?>">
                                            <?php echo $isValidArrangement ? '✅' : '⚠️'; ?>
                                        </span>
                                    </h3>
                                    <p class="card-description">
                                        <?php echo htmlspecialchars($hall['venue']); ?> • 
                                        <?php echo $hall['occupied_seats']; ?>/<?php echo $hall['total_seats']; ?> seats occupied
                                    </p>
                                </div>
                                <div class="text-sm">
                                    <div class="text-gray-600">Seating Pattern:</div>
                                    <div class="text-xs text-gray-500">Based on department distribution</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="seat-grid">
                                <?php for ($i = 1; $i <= $hall['total_seats']; $i++): ?>
                                    <?php 
                                    $isOccupied = isset($seatMap[$i]); 
                                    $allocation = $isOccupied ? $seatMap[$i] : null;
                                    $department = $isOccupied ? $allocation['department'] : '';
                                    $deptColor = $isOccupied ? getDepartmentColor($department) : '';
                                    ?>
                                    <div class="seat <?php echo $isOccupied ? 'seat-occupied' : 'seat-available'; ?>"
                                         data-department="<?php echo htmlspecialchars($department); ?>"
                                         style="<?php echo $isOccupied ? 'background-color: ' . $deptColor . '20; border-color: ' . $deptColor . '; color: ' . $deptColor . ';' : ''; ?>"
                                         title="<?php echo $isOccupied ? htmlspecialchars($allocation['matric_number'] . ' (' . $allocation['department'] . ')') : 'Available'; ?>">
                                        <div class="seat-number">#<?php echo $i; ?></div>
                                        <?php if ($isOccupied): ?>
                                            <div class="seat-dept"><?php echo htmlspecialchars(substr($department, 0, 3)); ?></div>
                                        <?php else: ?>
                                            <div class="seat-dept text-xs">Empty</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <!-- Department Legend -->
                            <div class="seating-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #f3f4f6; border-color: #d1d5db;"></div>
                                    <span>Available</span>
                                </div>
                                <?php 
                                $uniqueDepartments = array_unique(array_column($hallAllocations, 'department'));
                                foreach ($uniqueDepartments as $dept): 
                                    $color = getDepartmentColor($dept);
                                ?>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background-color: <?php echo $color; ?>20; border-color: <?php echo $color; ?>;"></div>
                                        <span><?php echo htmlspecialchars($dept); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!$isValidArrangement): ?>
                                <div class="validation-info validation-error mt-4">
                                    <strong>⚠️ Pattern Warning:</strong> Some seats don't follow the systematic allocation pattern.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($halls)): ?>
                    <div class="card">
                        <div class="card-content text-center py-8 text-gray-500">
                            No seating arrangements available. Add halls to view seating arrangements.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Hall Modal -->
    <div id="addHallModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Examination Hall</h3>
                <p class="modal-description">
                    Enter the details for the new examination hall.
                </p>
            </div>

            <form action="/exam-seat-allocation/api/add-hall.php" method="POST" class="ajax-form add-hall-form space-y-4">
                <div class="form-group">
                    <label for="name" class="form-label">Hall Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        placeholder="e.g., Main Auditorium"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="venue" class="form-label">Venue/Location</label>
                    <input 
                        type="text" 
                        id="venue" 
                        name="venue" 
                        class="form-input" 
                        placeholder="e.g., Block A, Ground Floor"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="totalSeats" class="form-label">Total Number of Seats</label>
                    <input 
                        type="number" 
                        id="totalSeats" 
                        name="totalSeats" 
                        class="form-input" 
                        placeholder="e.g., 120"
                        min="12" 
                        max="1000"
                        required
                    >
                    <div class="text-xs text-gray-500 mt-1">
                        Minimum 12 seats recommended for proper department allocation
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Hall</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
    <script>
        // Student Search Functionality
        document.getElementById('studentSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const searchType = document.getElementById('search_type').value;
            const searchTerm = document.getElementById('search_term').value.trim();
            const resultsDiv = document.getElementById('searchResults');
            
            if (!searchTerm) {
                alert('Please enter a search term');
                return;
            }
            
            // Show loading
            resultsDiv.innerHTML = '<div class="text-center py-4">Searching...</div>';
            resultsDiv.classList.remove('hidden');
            
            // Perform search via AJAX
            fetch('/exam-seat-allocation/api/search-student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search_type=${searchType}&search_term=${encodeURIComponent(searchTerm)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data && data.data.length > 0) {
                        displaySearchResults(data.data);
                    } else {
                        resultsDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <span class="icon">⚠️</span>
                                <div>No students found matching your search</div>
                            </div>
                        `;
                    }
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-error">
                            <span class="icon">❌</span>
                            <div>Search error: ${data.error}</div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-error">
                        <span class="icon">❌</span>
                        <div>Search failed: ${error.message}</div>
                    </div>
                `;
            });
        });
        
        function displaySearchResults(students) {
            const resultsDiv = document.getElementById('searchResults');
            
            let html = '<div class="space-y-4"><h4 class="font-semibold text-lg mb-4">Search Results:</h4>';
            
            students.forEach(student => {
                html += `
                    <div class="border rounded-lg p-4">
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Matric Number</p>
                                <p class="font-semibold">${student.matric_number}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Full Name</p>
                                <p class="font-semibold">${student.full_name}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Department</p>
                                <span class="badge badge-outline">${student.department}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Level</p>
                                <span class="badge badge-secondary">${student.level}</span>
                            </div>
                        </div>
                        ${student.allocation ? `
                        <div class="mt-3 p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="icon text-emerald-600">📍</span>
                                <span class="font-medium text-emerald-700">Seat Allocation</span>
                            </div>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-emerald-600">Hall:</span> ${student.allocation.hall_name}
                                </div>
                                <div>
                                    <span class="text-emerald-600">Seat:</span> #${student.allocation.seat_number}
                                </div>
                                <div>
                                    <span class="text-emerald-600">Period:</span> ${student.allocation.exam_period}
                                </div>
                            </div>
                        </div>
                        ` : `
                        <div class="mt-3 p-3 bg-gray-100 rounded-lg">
                            <span class="text-gray-600">No seat allocation found</span>
                        </div>
                        `}
                    </div>
                `;
            });
            
            html += '</div>';
            resultsDiv.innerHTML = html;
        }

        function confirmLogout(event) {
            event.preventDefault();
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = '/exam-seat-allocation/auth/admin/logout.php';
            }
        }
    </script>
</body>
</html>