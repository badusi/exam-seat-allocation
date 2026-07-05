<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Mode - ExamSeat Pro</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="flex items-center space-x-4">
                    <a href="/" class="btn btn-outline btn-sm">
                        ← Back to Home
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Demo Mode</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-12">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Try the Demo</h1>
                <p class="text-lg text-gray-600">
                    Experience the exam seat allocation system with sample data. All features are fully functional!
                </p>
            </div>

            <div class="mb-8">
                <div class="card" style="background: linear-gradient(to right, #ecfdf5, #f0fdfa);">
                    <div class="card-header">
                        <h3 class="card-title flex items-center space-x-2">
                            <span class="text-emerald-600">✓</span>
                            <span>Demo Mode Active</span>
                        </h3>
                    </div>
                    <div class="card-content">
                        <p class="text-gray-700 mb-4">
                            The system is running with sample data. All features are fully functional!
                        </p>
                        <div class="p-4 bg-white rounded-lg border">
                            <p class="text-sm font-medium text-gray-900 mb-2">Available Features:</p>
                            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                                <div>✓ Student registration and login</div>
                                <div>✓ Admin dashboard and management</div>
                                <div>✓ Seat allocation system</div>
                                <div>✓ Hall management</div>
                                <div>✓ Seating arrangements</div>
                                <div>✓ Real-time statistics</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mb-12">
                <div class="card">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <span class="text-emerald-600">👥</span>
                        </div>
                        <h3 class="card-title">Student Experience</h3>
                        <p class="card-description">Test the student seat allocation process</p>
                    </div>
                    <div class="card-content space-y-4">
                        <p class="text-sm text-gray-600">Try these sample credentials:</p>
                        <div class="space-y-2 text-sm">
                            <div class="p-3 bg-gray-50 rounded">
                                <div class="font-medium">Demo Student</div>
                                <div class="font-mono text-xs">Matric: CSC/2024/001</div>
                                <div class="font-mono text-xs">Email: student@demo.com</div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Or register a new account with any details</p>
                        <div class="space-y-2">
                            <a href="/auth/student/register.php" class="btn btn-primary w-full">Register New Student</a>
                            <a href="/auth/student/login.php" class="btn btn-outline w-full">Student Login</a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-slate-100 rounded-lg flex items-center justify-center">
                            <span class="text-slate-600">🛡️</span>
                        </div>
                        <h3 class="card-title">Admin Dashboard</h3>
                        <p class="card-description">Explore the administrative features</p>
                    </div>
                    <div class="card-content space-y-4">
                        <p class="text-sm text-gray-600">Demo admin credentials:</p>
                        <div class="p-3 bg-gray-50 rounded text-sm">
                            <div class="font-medium">Demo Admin</div>
                            <div class="font-mono text-xs">Email: admin@demo.com</div>
                            <div class="font-mono text-xs">Password: admin123</div>
                        </div>
                        <p class="text-sm text-gray-600">Admin features include:</p>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Manage examination halls</li>
                            <li>• View student allocations</li>
                            <li>• Monitor seating arrangements</li>
                            <li>• Track occupancy statistics</li>
                        </ul>
                        <a href="/auth/admin/login.php" class="btn w-full" style="background: #475569; color: white;">Admin Login</a>
                    </div>
                </div>
            </div>

            <div class="card" style="background: linear-gradient(to right, #dbeafe, #e0e7ff);">
                <div class="card-header">
                    <h3 class="card-title flex items-center space-x-2">
                        <span class="text-blue-600">🗄️</span>
                        <span>Production Setup</span>
                    </h3>
                </div>
                <div class="card-content">
                    <p class="text-gray-700 mb-4">
                        For production use, set up a MySQL database and configure the connection in config/database.php.
                    </p>
                    <div class="p-4 bg-white rounded-lg border">
                        <p class="text-sm font-medium text-gray-900 mb-2">Database Configuration:</p>
                        <div class="space-y-1 text-sm font-mono text-gray-600">
                            <div>Host: localhost</div>
                            <div>Database: exam_seat_allocator</div>
                            <div>Username: your_username</div>
                            <div>Password: your_password</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/main.js"></script>
</body>
</html>
