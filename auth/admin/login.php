<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Check if already logged in
$session = $sessionManager->getSession();
if ($session && $session['user_type'] === 'admin') {
    header('Location: /exam-seat-allocation/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check demo credentials first
        if ($email === 'admin@demo.com' && $password === 'admin123') {
            $sessionData = [
                'email' => 'admin@demo.com',
                'fullName' => 'Demo Admin',
                'role' => 'admin'
            ];
            
            $sessionManager->createSession('admin', 1, $sessionData);
            
            if (isset($_POST['ajax'])) {
                sendJsonResponse(true, $sessionData, null);
            } else {
                header('Location: /exam-seat-allocation/admin/dashboard.php');
                exit;
            }
        } else {
            // Check database
            $db = $database->getConnection();
            $query = "SELECT * FROM admins WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $admin['password'])) {
                    $sessionData = [
                        'email' => $admin['email'],
                        'fullName' => $admin['full_name'],
                        'role' => $admin['role']
                    ];
                    
                    $sessionManager->createSession('admin', $admin['id'], $sessionData);
                    
                    if (isset($_POST['ajax'])) {
                        sendJsonResponse(true, $sessionData, null);
                    } else {
                        header('Location: /exam-seat-allocation/admin/dashboard.php');
                        exit;
                    }
                } else {
                    $error = 'Invalid credentials';
                }
            } else {
                $error = 'Invalid credentials';
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
    <title>Admin Login - ExamSeat Pro</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
    <style>
        main.container.py-12 {
              margin-top: 20px;
            }
        /* Smaller form size for auth pages */
        .max-w-md {
        max-width: 400px !important;
        transform: scale(1.10);
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);">
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="flex items-center space-x-4">
                    <a href="/exam-seat-allocation/index.php" class="btn btn-outline btn-sm">
                        ← Back to Home
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Admin Login</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-12">
        <div class="max-w-md mx-auto">
            <div class="card">
                <div class="card-header text-center">
                    <div class="icon-xl mx-auto mb-4 bg-slate-100 rounded-full flex items-center justify-center">
                        <span class="text-slate-600">🛡️</span>
                    </div>
                    <h2 class="card-title">Admin Login</h2>
                    <p class="card-description text-lg">Access the administrative dashboard</p>
                </div>
                <div class="card-content">
                    <form method="POST" class="ajax-form login-form space-y-4">
                        <input type="hidden" name="ajax" value="1">
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="admin@university.edu"
                                required
                                style="font-size: 1.125rem; padding: 0.75rem;"
                            >
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
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

                        <button type="submit" class="btn btn-lg w-full" style="background: #475569; color: white;">
                            Login
                        </button>
                    </form>

                    <!-- <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">Demo Credentials:</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>Email: admin@demo.com</div>
                            <div>Password: admin123</div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </main>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
</body>
</html>
