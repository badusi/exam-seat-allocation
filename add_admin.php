<?php
$mysqli = new mysqli('localhost', 'root', '', 'exam_seat_allocator');


if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// New admin credentials
$email = "admin@polytechnic.edu";  // Change this to the new admin's email
$password = "admin123";    // Change this to a strong password
$full_name = "System Administrator";
$role = "admin";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into the database
$stmt = $mysqli->prepare("INSERT INTO admins (email, password, full_name, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $email, $hashed_password, $full_name, $role);

if ($stmt->execute()) {
    echo "✅ New admin added successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
