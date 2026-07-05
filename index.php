<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Federal Polytechnic Ayede - Smart Examination Seating System</title>
    <meta name="description" content="Intelligent exam seat allocation system for educational institutions">
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <div class="logo-icon">📚</div>
                    <h1 class="text-xl font-bold">Federal Polytechnic Ayede</h1>
                </a>
                <nav class="hidden-mobile">
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How it Works</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-20 px-4">
        <div class="container text-center">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                    Smart Examination Seating 
                    <span class="text-emerald-600" style="color: #288d0fff;">System</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8" style="color: #f9fafb;">
                    Streamline your examination process with intelligent seat distribution across departments and halls.
                    Ensure fair allocation and efficient management.
                </p>
                <div class="flex flex-col space-y-4 justify-center responsive-row">
                    <div class="login-buttons">
                        <a href="/exam-seat-allocation/auth/student/login.php" class="btn btn-primary btn-lg">
                            Student Login
                        </a>
                        <a href="/exam-seat-allocation/auth/admin/login.php" class="btn btn-outline btn-lg">
                            Admin Login
                        </a>
                    </div>
                    <!-- <a href="/demo.php" class="btn btn-secondary btn-lg">
                        Try Demo
                    </a> -->
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4" style="background: white;">
        <div class="container">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose Federal Polytechnic Ayede?</h2>
                <p class="text-lg text-gray-600">
                    Our intelligent system ensures fair and efficient seat allocation for all students
                </p>
            </div>

            <div class="grid grid-cols-4 gap-8">
                <div class="card">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                            <span class="text-emerald-600">👥</span>
                        </div>
                        <h3 class="card-title">Smart Distribution</h3>
                    </div>
                    <div class="card-content">
                        <p class="text-center text-gray-600">
                            Automatically distributes seats across departments to ensure balanced allocation
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-teal-100 rounded-full flex items-center justify-center">
                            <span class="text-teal-600">📍</span>
                        </div>
                        <h3 class="card-title">Multi-Hall Support</h3>
                    </div>
                    <div class="card-content">
                        <p class="text-center text-gray-600">
                            Seamlessly manages multiple examination halls with automatic overflow handling
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
                            <span class="text-orange-600">🛡️</span>
                        </div>
                        <h3 class="card-title">Secure & Reliable</h3>
                    </div>
                    <div class="card-content">
                        <p class="text-center text-gray-600">
                            Prevents duplicate registrations and maintains consistent seat assignments
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header text-center">
                        <div class="icon-xl mx-auto mb-4 bg-purple-100 rounded-full flex items-center justify-center">
                            <span class="text-purple-600">📚</span>
                        </div>
                        <h3 class="card-title">Easy Management</h3>
                    </div>
                    <div class="card-content">
                        <p class="text-center text-gray-600">
                            Comprehensive admin dashboard for managing halls, students, and seating arrangements
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-20 px-4" style="background: #f9fafb;">
        <div class="container">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
                <p class="text-lg text-gray-600">Simple steps to get your exam seat allocated</p>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="icon-xl mx-auto mb-6 bg-emerald-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        1
                    </div>
                    <h3 class="text-xl font-semibold mb-4">Enter Matric Number</h3>
                    <p class="text-gray-600">
                        Simply input your matriculation number to begin the seat allocation process
                    </p>
                </div>

                <div class="text-center">
                    <div class="icon-xl mx-auto mb-6 bg-teal-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        2
                    </div>
                    <h3 class="text-xl font-semibold mb-4">Get Seating Assignment</h3>
                    <p class="text-gray-600">
                        Our system automatically assigns you a seat based on your department and availability
                    </p>
                </div>

                <div class="text-center">
                    <div class="icon-xl mx-auto mb-6 bg-orange-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        3
                    </div>
                    <h3 class="text-xl font-semibold mb-4">Take Your Exam</h3>
                    <p class="text-gray-600">
                        Proceed to your assigned hall and seat number on the examination day
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #111827; color: white;" class="py-12 px-4">
        <div class="container text-center">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <div class="icon-xl bg-emerald-600 rounded-lg flex items-center justify-center text-white">
                    📚
                </div>
                <h3 class="text-xl font-bold">Federal Polytechnic Ayede</h3>
            </div>
            <p class="text-gray-400 mb-6">Intelligent exam seat allocation system for educational institutions</p>
            <div class="flex justify-center space-x-6">
                <a href="/exam-seat-allocation/auth/student/login.php" class="text-gray-400 hover:text-emerald-400">Student Login</a>
                <a href="/exam-seat-allocation/auth/admin/login.php" class="text-gray-400 hover:text-slate-400">Admin Login</a>
            </div>
        </div>
    </footer>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
</body>
</html>
