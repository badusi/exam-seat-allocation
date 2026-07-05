<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Check if already logged in
$session = $sessionManager->getSession();
if ($session && $session['user_type'] === 'student') {
    header('Location: /exam-seat-allocation/student/dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $success = 'Registration successful! You can now login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricNumber = strtoupper(trim($_POST['matricNumber'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    
    if (empty($matricNumber) || empty($email)) {
        $error = 'Please fill in all fields';
    } else {
        $db = $database->getConnection();
        
        // Check demo credentials first
        if ($matricNumber === 'CS2021010001' && $email === 'student@demo.com') {
            $sessionData = [
                'matricNumber' => 'CS2021010001',
                'email' => 'student@demo.com',
                'fullName' => 'Demo Student',
                'department' => 'Computer Science',
                'level' => '400'
            ];
            
            $sessionManager->createSession('student', 1, $sessionData);
            
            if (isset($_POST['ajax'])) {
                sendJsonResponse(true, $sessionData, null);
            } else {
                header('Location: /exam-seat-allocation/student/dashboard.php');
                exit;
            }
        } else {
            // Check database
            $query = "SELECT * FROM students WHERE matric_number = ? AND email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$matricNumber, $email]);
            
            if ($stmt->rowCount() > 0) {
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $sessionData = [
                    'matricNumber' => $student['matric_number'],
                    'email' => $student['email'],
                    'fullName' => $student['full_name'],
                    'department' => $student['department'],
                    'level' => $student['level'],
                    'role' => 'student'
                ];
                
                $sessionManager->createSession('student', $student['id'], $sessionData);
                
                if (isset($_POST['ajax'])) {
                    sendJsonResponse(true, $sessionData, null);
                } else {
                    header('Location: /exam-seat-allocation/student/dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid credentials';
                if (isset($_POST['ajax'])) {
                    sendJsonResponse(false, null, $error);
                }
            }
        }
    }
    
    if (isset($_POST['ajax']) && $error) {
        sendJsonResponse(false, null, $error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - ExamSeat Pro</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
    <style>
        main.container.py-12 {
              margin-top: 20px;
            }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="flex items-center space-x-4">
                    <a href="/exam-seat-allocation/index.php" class="btn btn-outline btn-sm">
                        ← Back to Home
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Student Login</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-12">
        <div class="max-w-md mx-auto">
            <div class="card">
                <div class="card-header text-center">
                    <div class="icon-xl mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                        <span class="text-emerald-600">👥</span>
                    </div>
                    <h2 class="card-title">Student Login</h2>
                    <p class="card-description text-lg">
                        Enter your details to access the exam seat allocation system
                    </p>
                </div>
                <div class="card-content">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <span class="icon">✓</span>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="ajax-form login-form space-y-4">
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="form-group">
                            <label for="matricNumber" class="form-label">Matriculation Number</label>
                            <input 
                                type="text" 
                                id="matricNumber" 
                                name="matricNumber" 
                                class="form-input" 
                                placeholder="e.g., CE2021010001"
                                required
                                style="font-size: 1.125rem; padding: 0.75rem;"
                            >
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="your.email@university.edu"
                                required
                                style="font-size: 1.125rem; padding: 0.75rem;"
                            >
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <span class="icon">✗</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            Login
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Don't have an account? 
                            <a href="/exam-seat-allocation/auth/student/register.php" class="text-emerald-600 hover:text-emerald-700 font-medium">
                                Register here
                            </a>
                        </p>
                    </div>

                    <!-- <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">Demo Credentials:</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>Matric: CSC/2024/001</div>
                            <div>Email: student@demo.com</div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </main>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
</body>
</html>
