<?php
// Start session
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "skills_gap_db");

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Validate inputs
    if (empty($email) || empty($password)) {
        die("Email and password are required.");
    }

    // Fetch user by email
    $stmt = $conn->prepare(
        "SELECT id, name, password, role FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user["password"])) {

            // Store session data
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            // Redirect based on role
            if ($user["role"] === "admin") {
                header("Location: ../admin_dashboard.html");
            } else {
                header("Location: ../dashboard.html");
            }
            exit();

        } else {
            die("Invalid email or password.");
        }

    } else {
        die("Invalid email or password.");
    }

    $stmt->close();
}

$conn->close();
?>
