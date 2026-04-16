<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Connection failed");
}

/* Auto score based on submission length */
function autoScore($submission) {
    $length = strlen(trim($submission));

    if ($length < 50) return 20;        // very weak
    if ($length < 150) return 45;       // beginner
    if ($length < 300) return 70;       // intermediate
    return 90;                          // advanced
}

/* Skill level */
function getUserLevel($score) {
    if ($score < 40) return "Beginner";
    if ($score < 70) return "Intermediate";
    return "Advanced";
}

/* Convert level to numeric (for DB) */
function levelToNumber($level) {
    if ($level === "Beginner") return 1;
    if ($level === "Intermediate") return 2;
    return 3; // Advanced
}

/* Fetch submissions */
$result = $conn->query(
    "SELECT ts.id, ts.submission, ts.score, ts.submitted_at,
            ts.user_id,
            s.id AS skill_id,
            u.name AS user_name,
            s.skill_name
     FROM task_submissions ts
     JOIN users u ON ts.user_id = u.id
     JOIN skill_tasks st ON ts.task_id = st.id
     JOIN skills s ON st.skill_id = s.id
     ORDER BY ts.submitted_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Submissions</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:20px; }
        table { width:100%; background:#fff; border-collapse:collapse; }
        th, td { padding:10px; border:1px solid #ccc; }
        th { background:#007bff; color:#fff; }
        textarea { width:100%; height:80px; }
        .beginner { color:red; font-weight:bold; }
        .intermediate { color:orange; font-weight:bold; }
        .advanced { color:green; font-weight:bold; }
    </style>
</head>
<body>

<h2>Auto-Graded Skill Submissions</h2>

<table>
<tr>
    <th>User</th>
    <th>Skill</th>
    <th>Submission</th>
    <th>Score (%)</th>
    <th>Level</th>
    <th>Date</th>
</tr>

<?php while ($row = $result->fetch_assoc()): 

    /* Auto-score if not scored yet */
    $score = $row['score'];
    if ($score === null) {
        $score = autoScore($row['submission']);

        $update = $conn->prepare(
            "UPDATE task_submissions SET score = ? WHERE id = ?"
        );
        $update->bind_param("ii", $score, $row['id']);
        $update->execute();
        $update->close();
    }

    $level = getUserLevel($score);
    $level_num = levelToNumber($level);

    /* Save/update user skill level (CRITICAL FIX) */
    $save = $conn->prepare(
        "INSERT INTO user_skills (user_id, skill_id, user_level)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE user_level = VALUES(user_level)"
    );
    $save->bind_param(
        "iii",
        $row['user_id'],
        $row['skill_id'],
        $level_num
    );
    $save->execute();
    $save->close();
?>

<tr>
    <td><?= htmlspecialchars($row['user_name']) ?></td>
    <td><?= htmlspecialchars($row['skill_name']) ?></td>
    <td>
        <textarea readonly><?= htmlspecialchars($row['submission']) ?></textarea>
    </td>
    <td><?= $score ?>%</td>
    <td class="<?= strtolower($level) ?>"><?= $level ?></td>
    <td><?= $row['submitted_at'] ?></td>
</tr>

<?php endwhile; ?>

</table>

</body>
</html>
