<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please login to view your results.");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];

/* Skill level function */
function getUserLevel($score) {
    if ($score < 40) return "Beginner";
    if ($score < 70) return "Intermediate";
    return "Advanced";
}

/* Fetch user's results */
$stmt = $conn->prepare(
    "SELECT s.skill_name,
            ts.score,
            ts.submitted_at
     FROM task_submissions ts
     JOIN skill_tasks st ON ts.task_id = st.id
     JOIN skills s ON st.skill_id = s.id
     WHERE ts.user_id = ?
     ORDER BY ts.submitted_at DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Skill Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 20px;
        }
        .box {
            max-width: 700px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background: #007bff;
            color: #fff;
        }
        .beginner { color: red; font-weight: bold; }
        .intermediate { color: orange; font-weight: bold; }
        .advanced { color: green; font-weight: bold; }
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>My Skill Test Results</h2>

    <?php if ($result->num_rows === 0): ?>
        <p>You have not submitted any tasks yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Skill</th>
            <th>Score</th>
            <th>Level</th>
            <th>Date</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()):
            $level = getUserLevel($row['score']);
        ?>
        <tr>
            <td><?= htmlspecialchars($row['skill_name']) ?></td>
            <td><?= $row['score'] ?>%</td>
            <td class="<?= strtolower($level) ?>"><?= $level ?></td>
            <td><?= $row['submitted_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>

    <a href="../../dashboard.html">← Back to Dashboard</a>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
