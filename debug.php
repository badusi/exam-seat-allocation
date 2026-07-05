<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Systematic Allocation - ExamSeat Pro</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
</head>
<body style="background: #f9fafb;">
    <header style="background: white;">
        <div class="container">
            <div class="header-content">
                <h1 class="text-2xl font-bold text-gray-900">Debug Systematic Allocation</h1>
                <div class="flex items-center space-x-4">
                    <button id="debugBtn" class="btn btn-primary">🔍 Debug Current State</button>
                    <button id="resetBtn" class="btn btn-danger">🗑️ Reset All Allocations</button>
                    <button id="testBtn" class="btn btn-secondary">🧪 Run Test Allocations</button>
                    <a href="/exam-seat-allocation/admin/dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-8">
        <!-- Systematic Pattern Explanation -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">🎯 Systematic Allocation Pattern</h3>
            </div>
            <div class="card-content">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <h4 class="font-semibold mb-2">Department Order:</h4>
                        <ol class="space-y-1">
                            <li>1. Computer Science</li>
                            <li>2. Statistics</li>
                            <li>3. Electrical Engineering</li>
                            <li>4. Civil Engineering</li>
                            <li>5. Mechanical Engineering</li>
                            <li>6. Chemical Engineering</li>
                        </ol>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Continued:</h4>
                        <ol start="7" class="space-y-1">
                            <li>7. Agricultural Engineering</li>
                            <li>8. Food Science Technology</li>
                            <li>9. Biology</li>
                            <li>10. Physics</li>
                            <li>11. Chemistry</li>
                            <li>12. Mathematics</li>
                        </ol>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2">Seat Pattern:</h4>
                        <div class="space-y-1">
                            <p><strong>CSC:</strong> 1, 13, 25, 37...</p>
                            <p><strong>SLT:</strong> 2, 14, 26, 38...</p>
                            <p><strong>EEE:</strong> 3, 15, 27, 39...</p>
                            <p><strong>CVE:</strong> 4, 16, 28, 40...</p>
                            <p class="text-xs text-gray-500">Formula: (dept_index + 1) + (student_count × 12)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="debugResults" class="space-y-6"></div>
    </main>

    <script>
        document.getElementById('debugBtn').addEventListener('click', async function() {
            try {
                const response = await fetch('/api/debug-allocations.php');
                const result = await response.json();
                
                if (result.success) {
                    displayDebugResults(result.data);
                } else {
                    alert('Debug failed: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        document.getElementById('resetBtn').addEventListener('click', async function() {
            if (!confirm('Are you sure you want to reset ALL allocations? This cannot be undone!')) {
                return;
            }
            
            try {
                const response = await fetch('/api/reset-allocations.php', {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ All allocations have been reset!');
                    document.getElementById('debugResults').innerHTML = '';
                } else {
                    alert('❌ Reset failed: ' + result.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        document.getElementById('testBtn').addEventListener('click', async function() {
            if (!confirm('This will create test allocations for demonstration. Continue?')) {
                return;
            }
            
            const testStudents = [
                {matric: 'CSC/2024/001', email: 'csc1@test.com'},
                {matric: 'CSC/2024/002', email: 'csc2@test.com'},
                {matric: 'SLT/2024/001', email: 'slt1@test.com'},
                {matric: 'SLT/2024/002', email: 'slt2@test.com'},
                {matric: 'EEE/2024/001', email: 'eee1@test.com'},
                {matric: 'CVE/2024/001', email: 'cve1@test.com'}
            ];
            
            for (let student of testStudents) {
                try {
                    const formData = new FormData();
                    formData.append('matricNumber', student.matric);
                    
                    const response = await fetch('/api/allocate-seat.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    console.log(`${student.matric}: ${result.success ? 'Success' : 'Failed'}`);
                } catch (error) {
                    console.error(`Error allocating ${student.matric}:`, error);
                }
            }
            
            alert('Test allocations completed! Click Debug to see results.');
        });

        function displayDebugResults(data) {
            const container = document.getElementById('debugResults');
            
            let html = `
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">🔍 Debug Summary</h3>
                    </div>
                    <div class="card-content">
                        <div class="grid grid-cols-4 gap-4">
                            <div class="stat-card">
                                <div class="stat-title">Total Allocations</div>
                                <div class="stat-value">${data.total_allocations}</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-title">Total Halls</div>
                                <div class="stat-value">${data.total_halls}</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-title">Pattern Violations</div>
                                <div class="stat-value ${data.violation_count > 0 ? 'text-red-600' : 'text-green-600'}">${data.violation_count}</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-title">System Status</div>
                                <div class="stat-value ${data.violation_count > 0 ? 'text-red-600' : 'text-green-600'}">
                                    ${data.violation_count > 0 ? '❌ Issues' : '✅ Perfect'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Current Allocations
            if (data.allocations && data.allocations.length > 0) {
                html += `
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📋 Current Allocations</h3>
                        </div>
                        <div class="card-content">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Matric</th>
                                        <th>Department</th>
                                        <th>Hall</th>
                                        <th>Seat</th>
                                        <th>Expected Pattern</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                const departments = [
                    'Computer Science', 'Statistics', 'Electrical Engineering', 'Civil Engineering',
                    'Mechanical Engineering', 'Chemical Engineering', 'Agricultural Engineering',
                    'Food Science Technology', 'Biology', 'Physics', 'Chemistry', 'Mathematics'
                ];
                
                data.allocations.forEach(allocation => {
                    const deptIndex = departments.indexOf(allocation.department);
                    const deptCount = data.allocations.filter(a => a.department === allocation.department && a.seat_number <= allocation.seat_number).length - 1;
                    const expectedSeat = (deptIndex + 1) + (deptCount * 12);
                    const isCorrect = allocation.seat_number == expectedSeat;
                    
                    html += `
                        <tr>
                            <td class="font-mono text-sm">${allocation.matric_number}</td>
                            <td><span class="badge badge-outline">${allocation.department}</span></td>
                            <td>${allocation.hall_name}</td>
                            <td><span class="badge badge-primary">#${allocation.seat_number}</span></td>
                            <td><span class="text-sm text-gray-500">#${expectedSeat}</span></td>
                            <td>
                                <span class="badge ${isCorrect ? 'badge-primary' : 'badge-danger'}">
                                    ${isCorrect ? '✅ Correct' : '❌ Wrong'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            // Department Distribution
            if (data.department_distribution && Object.keys(data.department_distribution).length > 0) {
                html += `
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 Department Distribution</h3>
                        </div>
                        <div class="card-content">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Students</th>
                                        <th>Expected Seats</th>
                                        <th>Halls Used</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                Object.keys(data.department_distribution).forEach(dept => {
                    const halls = data.department_distribution[dept];
                    const totalStudents = Object.values(halls).reduce((sum, count) => sum + count, 0);
                    const hallList = Object.keys(halls).join(', ');
                    
                    const deptIndex = departments.indexOf(dept);
                    const expectedSeats = [];
                    for (let i = 0; i < totalStudents; i++) {
                        expectedSeats.push((deptIndex + 1) + (i * 12));
                    }
                    
                    html += `
                        <tr>
                            <td><span class="badge badge-outline">${dept}</span></td>
                            <td><strong>${totalStudents}</strong></td>
                            <td><span class="text-sm font-mono">${expectedSeats.join(', ')}</span></td>
                            <td><span class="text-sm">${hallList}</span></td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }
    </script>
</body>
</html>
