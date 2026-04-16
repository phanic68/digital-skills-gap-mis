<?php
session_start();

// Protect page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

// Fetch skills
$skills = $conn->query("SELECT id, skill_name FROM skills");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test My Skills</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .box {
            background: #ffffff;
            padding: 25px;
            width: 350px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
        }
    </style>
</head>

<body>

<div class="box">
    <h2>Test Your Skill</h2>

    <form action="backend/test/start_test.php" method="GET">
        <select name="skill_id" required>
            <option value="">-- Select Skill --</option>

            <?php while ($row = $skills->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['skill_name']) ?>
                </option>
            <?php endwhile; ?>

        </select>

        <button type="submit">Start Test</button>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
