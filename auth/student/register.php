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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricNumber = strtoupper(trim($_POST['matricNumber'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $fullName = trim($_POST['fullName'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $level = trim($_POST['level'] ?? '');
    
    // Debug: Check what values are being received
    error_log("Registration attempt - Level: " . $level . ", Department: " . $department);
    
    if (empty($matricNumber) || empty($email) || empty($fullName) || empty($department) || empty($level)) {
        $error = 'Please fill in all fields';
        error_log("Registration failed - Empty fields detected");
    } else {
        $db = $database->getConnection();
        
        // Check if student already exists
        $query = "SELECT id FROM students WHERE matric_number = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$matricNumber, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'A student with this matric number or email already exists';
            error_log("Registration failed - Student already exists");
        } else {
            // Insert new student
            $query = "INSERT INTO students (matric_number, email, full_name, department, level) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            error_log("Executing query with level: " . $level);
            
            if ($stmt->execute([$matricNumber, $email, $fullName, $department, $level])) {
                error_log("Registration successful for: " . $matricNumber);
                $success = 'Registration successful! Redirecting to login page...';
                
                // Store success message in session for login page
                $_SESSION['registration_success'] = 'Registration successful! Please login with your credentials.';
                
                // Redirect to login page after showing success message
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '/exam-seat-allocation/auth/student/login.php?registered=true';
                    }, 2000);
                </script>";
            } else {
                $error = 'Registration failed. Please try again.';
                $errorInfo = $stmt->errorInfo();
                error_log("Registration failed - SQL Error: " . print_r($errorInfo, true));
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Federal Polytechnic Ayede</title>
    <link rel="stylesheet" href="/exam-seat-allocation/assets/css/style.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            align-items: center;
            justify-content: center;
        }
        .container{
            line-height: 0.3em;
        }
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #7f1d1d;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
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
                    <h1 class="text-2xl font-bold text-gray-900">Student Registration</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-12">
        <div class="max-w-md mx-auto">
            <div class="card">
                <div class="card-header text-center">
                    <h2 class="card-title"> Create Student Account</h2>
                    <p class="card-description text-lg">Register to access the exam seat allocation system</p>
                </div>
                <div class="card-content">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <span class="icon">✅</span>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div class="form-group">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input 
                                type="text" 
                                id="fullName" 
                                name="fullName" 
                                class="form-input" 
                                placeholder="John Doe"
                                required
                                value="<?php echo htmlspecialchars($_POST['fullName'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="matricNumber" class="form-label">Matriculation Number</label>
                            <input 
                                type="text" 
                                id="matricNumber" 
                                name="matricNumber" 
                                class="form-input" 
                                placeholder="e.g., CS202101001"
                                required
                                value="<?php echo htmlspecialchars($_POST['matricNumber'] ?? ''); ?>"
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
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="department" class="form-label">Department</label>
                            <select id="department" name="department" class="form-select" required>
                                <option value="">Select your department</option>
                                <?php foreach (getDepartments() as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" 
                                        <?php echo (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="level" class="form-label">Level</label>
                            <select id="level" name="level" class="form-select" required>
                                <option value="">Select your level</option>
                                <option value="ND 1" <?php echo (isset($_POST['level']) && $_POST['level'] === 'ND 1') ? 'selected' : ''; ?>>ND 1</option>
                                <option value="ND 2" <?php echo (isset($_POST['level']) && $_POST['level'] === 'ND 2') ? 'selected' : ''; ?>>ND 2</option>
                                <option value="HND 1" <?php echo (isset($_POST['level']) && $_POST['level'] === 'HND 1') ? 'selected' : ''; ?>>HND 1</option>
                                <option value="HND 2" <?php echo (isset($_POST['level']) && $_POST['level'] === 'HND 2') ? 'selected' : ''; ?>>HND 2</option>
                            </select>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <span class="icon">✗</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            Create Account
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Already have an account? 
                            <a href="/exam-seat-allocation/auth/student/login.php" class="text-emerald-600 hover:text-emerald-700 font-medium">
                                Login here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="/exam-seat-allocation/assets/js/main.js"></script>
</body>
</html>