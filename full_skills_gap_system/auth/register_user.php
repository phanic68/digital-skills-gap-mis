<?php
// Start session
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "skills_gap_db");

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data and sanitize
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $role = isset($_POST["role"]) ? $_POST["role"] : "user";

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        die("All fields are required.");
    }

    // Allow only valid roles (SECURITY)
    if ($role !== "admin" && $role !== "user") {
        $role = "user";
    }

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        die("Email already registered.");
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database (UPDATED)
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        // Registration successful
        header("Location: ../login.html");
        exit();
    } else {
        echo "Registration failed. Please try again.";
    }

    // Close statements
    $stmt->close();
    $checkEmail->close();
}

// Close connection
$conn->close();
?>
