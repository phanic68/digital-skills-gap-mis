
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
$msg = "";
$recommendations = "";
$showChoice = false;

$skill_id = $_GET['skill_id'] ?? '';
$level = $_GET['level'] ?? '';

/* =========================
   GET USER CURRENT LEVEL
========================= */
function getNextLevel($current) {
    if ($current == "Beginner") return "Intermediate";
    if ($current == "Intermediate") return "Advanced";
    return "Mastered";
}

function getSkillRecommendations($skill_name) {
    $skill_name = strtolower($skill_name);

    if (strpos($skill_name, "program") !== false) {
        return "
        • https://www.freecodecamp.org  
        • https://www.w3schools.com  
        • https://www.codecademy.com";
    }

    if (strpos($skill_name, "excel") !== false) {
        return "
        • https://exceljet.net  
        • https://learn.microsoft.com  
        • YouTube Excel Tutorials";
    }

    return "
    • Coursera  
    • LinkedIn Learning  
    • YouTube Tutorials";
}

/* =========================
   FETCH TASK
========================= */
$taskData = null;

if (!empty($skill_id) && !empty($level)) {

    // Check user's current level
    $progressCheck = $conn->prepare(
        "SELECT current_level FROM user_skill_progress 
         WHERE user_id = ? AND skill_id = ?"
    );
    $progressCheck->bind_param("ii", $user_id, $skill_id);
    $progressCheck->execute();
    $result = $progressCheck->get_result();
    $progress = $result->fetch_assoc();
    $progressCheck->close();

    if ($progress && $progress['current_level'] != $level) {
        die("You must complete your current level first.");
    }

    $stmt = $conn->prepare(
        "SELECT id, task_description, level
         FROM skill_tasks 
         WHERE skill_id = ? AND level = ?"
    );
    $stmt->bind_param("is", $skill_id, $level);
    $stmt->execute();
    $taskData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* =========================
   HANDLE SUBMISSION
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task_id = $_POST['task_id'];
    $submissionText = trim($_POST['submission'] ?? "");
    $submissionLink = trim($_POST['submission_link'] ?? "");
    $finalSubmission = "";

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

    if (!empty($submissionText)) {
        $finalSubmission = $submissionText;
    } elseif (!empty($submissionLink)) {
        $finalSubmission = "LINK: " . $submissionLink;
    }

    if (!empty($finalSubmission)) {

        $stmt = $conn->prepare(
            "INSERT INTO task_submissions (user_id, task_id, submission)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $user_id, $task_id, $finalSubmission);
        $stmt->execute();
        $stmt->close();

        // AUTO PASS (you can later add grading logic)
        $current_level = $_POST['current_level'];
        $next_level = getNextLevel($current_level);

        if ($next_level == "Mastered") {
            $conn->query("UPDATE user_skill_progress 
                          SET current_level='Mastered', status='Completed' 
                          WHERE user_id=$user_id AND skill_id=$skill_id");
            $msg = "🎉 Skill Mastered Successfully!";
        } else {

            $update = $conn->prepare(
                "UPDATE user_skill_progress 
                 SET current_level=?, status='Passed' 
                 WHERE user_id=? AND skill_id=?"
            );
            $update->bind_param("sii", $next_level, $user_id, $skill_id);
            $update->execute();
            $update->close();

            if ($current_level == "Beginner") {

                // Get skill name
                $skillRes = $conn->query("SELECT skill_name FROM skills WHERE id=$skill_id");
                $skillName = $skillRes->fetch_assoc()['skill_name'];

                $recommendations = getSkillRecommendations($skillName);

                $msg = "🎉 You passed Beginner Level!";
            }

            if ($current_level == "Intermediate") {
                $showChoice = true;
                $msg = "🎉 You passed Intermediate Level!";
            }
        }
    } else {
        $msg = "Please submit text, link, or file.";
    }
}
?><?php if ($msg) echo "<p style='color:green'>$msg</p>"; ?><?php if ($recommendations): ?>
    <div style="background:#e9f7ef;padding:10px;margin-top:10px;">
        <strong>Recommended Learning Resources:</strong><br>
        <?= nl2br($recommendations) ?>
    </div>
<?php endif; ?>

<?php if ($showChoice): ?>
    <div style="margin-top:15px;">
        <form method="GET">
            <input type="hidden" name="skill_id" value="<?= $skill_id ?>">
            <input type="hidden" name="level" value="Advanced">
            <button type="submit">Proceed to Advanced</button>
        </form>
    </div>
<?php endif; ?>