<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];
$taskData = null;
$msg = "";

$skill_id = $_GET['skill_id'] ?? '';
$level = $_GET['level'] ?? '';

/* Function to get next required level */
function getNextAllowedLevel($currentLevel) {
    if ($currentLevel == "Beginner") return "Intermediate";
    if ($currentLevel == "Intermediate") return "Advanced";
    return "Beginner";
}

/* Fetch task when skill AND level are selected */
if (!empty($skill_id) && !empty($level)) {

    $currentLevel = null;

    $stmt = $conn->prepare(
        "SELECT current_level FROM user_skill_progress 
         WHERE user_id = ? AND skill_id = ?"
    );
    $stmt->bind_param("ii", $user_id, $skill_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $currentLevel = $row['current_level'];
    }
    $stmt->close();

    $allowedLevel = getNextAllowedLevel($currentLevel);

    if ($level !== $allowedLevel) {
        $msg = "You must complete $allowedLevel level first before accessing this level.";
    } else {
        $stmt = $conn->prepare(
            "SELECT st.id, st.task_description, st.level
             FROM skill_tasks st 
             WHERE st.skill_id = ? AND st.level = ?"
        );
        $stmt->bind_param("is", $skill_id, $level);
        $stmt->execute();
        $taskData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

/* Handle submission */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task_id = $_POST['task_id'];
    $skill_id = $_POST['skill_id'];
    $level = $_POST['level'];

    $submissionText = trim($_POST['submission'] ?? "");
    $submissionLink = trim($_POST['submission_link'] ?? "");
    $finalSubmission = "";

    /* Handle file upload */
    if (!empty($_FILES['submission_file']['name'])) {
        $uploadDir = "../../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['submission_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $targetPath)) {
            $finalSubmission = "FILE: " . $fileName;
        }
    }

    /* Prefer text, then link, then file */
    if (!empty($submissionText)) {
        $finalSubmission = $submissionText;
    } elseif (!empty($submissionLink)) {
        $finalSubmission = "LINK: " . $submissionLink;
    }

    if (!empty($finalSubmission)) {

        /* ============================= */
        /* ===== AUTOMATIC GRADING ===== */
        /* ============================= */

        $auto_score = 0;

        // Basic grading logic
        if (!empty($submissionText) && strlen($submissionText) > 100) {
            $auto_score = 5;
        } elseif (!empty($submissionLink)) {
            $auto_score = 3;
        } elseif (strpos($finalSubmission, "FILE:") !== false) {
            $auto_score = 4;
        }

        /* ============================= */
        /* SAVE TO task_submissions     */
        /* ============================= */

        $stmt = $conn->prepare(
            "INSERT INTO task_submissions (user_id, task_id, submission)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $user_id, $task_id, $finalSubmission);
        $stmt->execute();
        $stmt->close();

        /* ============================= */
        /* SAVE TO user_answers (NEW)   */
        /* ============================= */

        $stmt = $conn->prepare(
            "INSERT INTO user_answers 
            (user_id, question_id, answer_text, auto_score) 
            VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iisi", $user_id, $task_id, $finalSubmission, $auto_score);
        $stmt->execute();
        $stmt->close();

        /* ============================= */
        /* UPDATE USER PROGRESS         */
        /* ============================= */

        $stmt = $conn->prepare(
            "INSERT INTO user_skill_progress (user_id, skill_id, current_level)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE current_level = VALUES(current_level)"
        );
        $stmt->bind_param("iis", $user_id, $skill_id, $level);
        $stmt->execute();
        $stmt->close();

        $msg = "Task submitted successfully! $level level completed. Auto Score: $auto_score";
    } else {
        $msg = "Please submit code, a link, or a file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Skill Task</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .box {
            background: #fff;
            padding: 25px;
            width: 520px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        select, textarea, input, button {
            width: 100%;
            margin-top: 10px;
            padding: 10px;
        }
        textarea { height: 140px; }
        button {
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .hint { font-size: 13px; color: #666; }
        .level-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        .Beginner { background: #28a745; color: white; }
        .Intermediate { background: #ffc107; color: black; }
        .Advanced { background: #dc3545; color: white; }
        hr { margin: 15px 0; }
    </style>
</head>
<body>

<div class="box">
    <h3>Skill Test</h3>

    <?php if ($msg) echo "<p style='color:green'>$msg</p>"; ?>

    <form method="GET">
        <select name="skill_id" required>
            <option value="">-- Select Skill --</option>
            <?php
            $skills = $conn->query("SELECT id, skill_name FROM skills");
            while ($row = $skills->fetch_assoc()) {
                $selected = ($skill_id == $row['id']) ? "selected" : "";
                echo "<option value='{$row['id']}' $selected>{$row['skill_name']}</option>";
            }
            ?>
        </select>

        <select name="level" required>
            <option value="">-- Select Level --</option>
            <option value="Beginner" <?= ($level=="Beginner")?"selected":"" ?>>Beginner</option>
            <option value="Intermediate" <?= ($level=="Intermediate")?"selected":"" ?>>Intermediate</option>
            <option value="Advanced" <?= ($level=="Advanced")?"selected":"" ?>>Advanced</option>
        </select>

        <button type="submit">Load Task</button>
    </form>

    <?php if ($taskData): ?>
        <hr>

        <div class="level-badge <?= $taskData['level'] ?>">
            <?= $taskData['level'] ?> Level
        </div>

        <p><strong>Task:</strong></p>
        <p><?= htmlspecialchars($taskData['task_description']) ?></p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="task_id" value="<?= $taskData['id'] ?>">
            <input type="hidden" name="skill_id" value="<?= $skill_id ?>">
            <input type="hidden" name="level" value="<?= $level ?>">

            <textarea name="submission" placeholder="Paste your code or written work here..."></textarea>

            <input type="url" name="submission_link" placeholder="OR paste project link (GitHub, Drive, Figma, Behance)">
            <div class="hint">For design skills, paste a project link</div>

            <input type="file" name="submission_file" accept=".jpg,.png,.pdf,.zip">
            <div class="hint">Upload images, PDF, or ZIP (for design tasks)</div>

            <button type="submit">Submit Task</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>