<?php
// Start session
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "skills_gap_db");

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $skill = trim($_POST["skill"]);

    if (empty($skill)) {
        $message = "Skill name is required.";
    } else {

        // Insert skill into database
        $stmt = $conn->prepare("INSERT INTO skills (skill_name) VALUES (?)");
        $stmt->bind_param("s", $skill);

        if ($stmt->execute()) {

            // Get newly inserted skill ID
            $new_skill_id = $stmt->insert_id;

           // 🔥 Automatic Default Tasks (Labeled Clearly)
$default_tasks = [
    [
        'Beginner',
        '[Beginner] Explain what ' . $skill . ' is and mention two tools used in it.'
    ],
    [
        'Intermediate',
        '[Intermediate] Demonstrate how ' . $skill . ' can be applied in a real-life situation.'
    ],
    [
        'Advanced',
        '[Advanced] Complete a practical project using ' . $skill . ' and explain your process.'
    ]
];

            $task_stmt = $conn->prepare(
                "INSERT INTO skill_tasks (skill_id, level, task_description)
                 VALUES (?, ?, ?)"
            );

            foreach ($default_tasks as $task) {
                $task_stmt->bind_param("iss", $new_skill_id, $task[0], $task[1]);
                $task_stmt->execute();
            }

            $task_stmt->close();

            $message = "Skill added successfully with automatic tasks.";
        } else {
            $message = "Failed to add skill.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Skill | Skills Gap System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .box {
            background: #ffffff;
            padding: 25px;
            width: 350px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        h2 {
            margin-bottom: 15px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .message {
            margin-bottom: 15px;
            color: green;
        }

        a {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>

<body>

<div class="box">
    <h2>Add Skill</h2>

    <?php if (!empty($message)): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="skill" placeholder="Enter skill name" required>
        <button type="submit">Add Skill</button>
    </form>

    <a href="../../dashboard.html">Back to Dashboard</a>
</div>

</body>
</html>