<?php
session_start();

/* Admin only */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Connection failed");
}

$msg = "";
$error = "";

/* Handle form submission */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $skill_id = $_POST['skill_id'] ?? '';
    $level = $_POST['level'] ?? '';
    $task = trim($_POST['task_description'] ?? '');

    if (empty($skill_id) || empty($task) || empty($level)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO skill_tasks (skill_id, task_description, level)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $skill_id, $task, $level);

        if ($stmt->execute()) {
            $msg = "✅ Task added successfully!";
        } else {
            $error = "Failed to add task.";
        }
        $stmt->close();
    }
}

/* Fetch skills */
$skills = $conn->query("SELECT id, skill_name FROM skills");
$hasSkills = ($skills && $skills->num_rows > 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Skill Task</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Global styles -->
    <link rel="stylesheet" href="../../css/style.css">
</head>

<body>

<div class="box">
    <h2>Add Skill Task</h2>

    <?php if ($msg): ?>
        <div class="success"><?= $msg ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if (!$hasSkills): ?>
        <div class="error">
            ⚠️ No skills found.<br>
            Please add skills first before creating tasks.
        </div>

        <a href="../../skills.html" class="btn-link">Go Add Skills</a>

    <?php else: ?>

        <form method="POST">

            <label><strong>Select Skill</strong></label>
            <select name="skill_id" required>
                <option value="">-- Select Skill --</option>
                <?php while ($row = $skills->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>">
                        <?= htmlspecialchars($row['skill_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label><strong>Select Level</strong></label>
            <select name="level" required>
                <option value="">-- Select Level --</option>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
            </select>

            <label class="hint">
                Describe what the user must do to prove this skill
            </label>

            <textarea
                name="task_description"
                placeholder="Example: Build a simple login form using HTML & CSS"
                required></textarea>

            <button type="submit">Add Task</button>

        </form>

    <?php endif; ?>
</div>

</body>
</html>