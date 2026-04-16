<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Connection failed");
}

$required = 70;

$result = $conn->query(
    "SELECT u.name, s.skill_name, ts.score
     FROM task_submissions ts
     JOIN users u ON ts.user_id = u.id
     JOIN skill_tasks st ON ts.task_id = st.id
     JOIN skills s ON st.skill_id = s.id
     WHERE ts.score IS NOT NULL"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Skills Gap Report</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f8;
            padding: 20px;
        }
        table {
            width: 100%;
            background: #fff;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
        }
        th {
            background: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>

<h2>Skills Gap Report</h2>

<table>
<tr>
    <th>User</th>
    <th>Skill</th>
    <th>Score</th>
    <th>Required</th>
    <th>Gap</th>
</tr>

<?php while ($row = $result->fetch_assoc()):
    $gap = max(0, $required - $row['score']);
?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['skill_name']) ?></td>
    <td><?= $row['score'] ?></td>
    <td><?= $required ?></td>
    <td><?= $gap ?></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
