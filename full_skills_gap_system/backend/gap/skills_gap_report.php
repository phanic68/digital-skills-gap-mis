<?php
session_start();

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

$user_id = $_SESSION["user_id"] ?? 0;

// Fetch skills gap + learning resource
$query = "
SELECT s.skill_name,
       s.learning_resource,
       rs.required_level,
       us.user_level,
       (rs.required_level - IFNULL(us.user_level, 0)) AS gap
FROM required_skills rs
JOIN skills s ON rs.skill_id = s.id
LEFT JOIN user_skills us 
    ON rs.skill_id = us.skill_id AND us.user_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Skills Gap Report</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:20px; }
        .box { background:#fff; max-width:800px; margin:auto; padding:20px; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:10px; border-bottom:1px solid #ccc; }
        th { background:#dc3545; color:#fff; }
        .ok { color:green; }
        .gap { color:red; font-weight:bold; }
        a.learn { color:#007bff; text-decoration:none; }
    </style>
</head>
<body>

<div class="box">
<h2>Skills Gap Report</h2>

<table>
<tr>
    <th>Skill</th>
    <th>Required Level</th>
    <th>Your Level</th>
    <th>Status</th>
    <th>Recommendation</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row["skill_name"]) ?></td>
    <td><?= $row["required_level"] ?></td>
    <td><?= $row["user_level"] ?? 0 ?></td>
    <td>
        <?php if ($row["gap"] > 0): ?>
            <span class="gap">Gap Exists</span>
        <?php else: ?>
            <span class="ok">OK</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($row["gap"] > 0 && !empty($row["learning_resource"])): ?>
            <a class="learn" href="<?= htmlspecialchars($row["learning_resource"]) ?>" target="_blank">
                Learn Here
            </a>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

<br>
<a href="../../dashboard.html">← Back to Dashboard</a>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
