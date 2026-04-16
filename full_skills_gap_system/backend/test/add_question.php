<?php
session_start();

/* OPTIONAL: lock to admin later
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../login.html");
    exit();
}
*/

// Database connection
$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

$message = "";

/* Save question */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $skill_id = $_POST["skill_id"];
    $question = trim($_POST["question"]);
    $a = trim($_POST["option_a"]);
    $b = trim($_POST["option_b"]);
    $c = trim($_POST["option_c"]);
    $d = trim($_POST["option_d"]);
    $correct = $_POST["correct"];

    if (!$skill_id || !$question || !$a || !$b || !$c || !$d) {
        $message = "All fields are required.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO skill_questions
            (skill_id, question, option_a, option_b, option_c, option_d, correct_option)
            VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param("issssss",
            $skill_id, $question, $a, $b, $c, $d, $correct
        );

        if ($stmt->execute()) {
            $message = "Question added successfully.";
        } else {
            $message = "Failed to add question.";
        }
        $stmt->close();
    }
}

/* Load skills */
$skills = $conn->query("SELECT id, skill_name FROM skills");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Skill Question</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 20px;
        }
        .box {
            background: #fff;
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border-radius: 6px;
        }
        input, textarea, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
        }
        button {
            background: #007bff;
            color: #fff;
            border: none;
        }
        .msg {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

<div class="box">
    <h2>Add Test Question</h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <select name="skill_id" required>
            <option value="">-- Select Skill --</option>
            <?php while ($s = $skills->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>">
                    <?= htmlspecialchars($s['skill_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <textarea name="question" placeholder="Question" required></textarea>

        <input type="text" name="option_a" placeholder="Option A" required>
        <input type="text" name="option_b" placeholder="Option B" required>
        <input type="text" name="option_c" placeholder="Option C" required>
        <input type="text" name="option_d" placeholder="Option D" required>

        <select name="correct" required>
            <option value="">Correct Answer</option>
            <option value="a">Option A</option>
            <option value="b">Option B</option>
            <option value="c">Option C</option>
            <option value="d">Option D</option>
        </select>

        <button type="submit">Save Question</button>
    </form>

    <br>
    <a href="../../admin_dashboard.html">← Back to Dashboard</a>
</div>

</body>
</html>

<?php $conn->close(); ?>
