<?php
session_start();

// Only admins can access
if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed.");
}

$msg = "";

/* Handle Admin Score Update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $answer_id = $_POST['answer_id'];
    $admin_score = $_POST['admin_score'];

    $stmt = $conn->prepare(
        "UPDATE user_answers 
         SET admin_score = ? 
         WHERE id = ?"
    );
    $stmt->bind_param("ii", $admin_score, $answer_id);
    $stmt->execute();
    $stmt->close();

    $msg = "Score updated successfully!";
}

/* Determine which user column to use */
$userColumn = null;
$userCols = $conn->query("SHOW COLUMNS FROM users");
$columns = [];
while ($col = $userCols->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// Priority: full_name -> name -> username -> first_name + last_name
if (in_array('full_name', $columns)) {
    $userColumn = "u.full_name AS display_name";
} elseif (in_array('name', $columns)) {
    $userColumn = "u.name AS display_name";
} elseif (in_array('username', $columns)) {
    $userColumn = "u.username AS display_name";
} elseif (in_array('first_name', $columns) && in_array('last_name', $columns)) {
    $userColumn = "CONCAT(u.first_name, ' ', u.last_name) AS display_name";
} else {
    die("No valid user name column found. Please add full_name, name, username, or first_name + last_name in users table.");
}

/* Fetch All Submissions */
$result = $conn->query(
    "SELECT ua.id, ua.answer_text, ua.auto_score, ua.admin_score,
            $userColumn,
            st.task_description
     FROM user_answers ua
     JOIN users u ON ua.user_id = u.id
     JOIN skill_tasks st ON ua.question_id = st.id
     ORDER BY ua.created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Review</title>
    <style>
        body { font-family: Arial; background: #f4f6f8; padding: 20px; }
        h2 { text-align: center; color: #2d3748; }
        table { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
        input { width: 60px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; }
        button { padding: 5px 10px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 4px; }
        button:hover { background: #218838; }
        .msg { color: green; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<h2>Admin Review Panel</h2>

<?php if ($msg) echo "<p class='msg'>$msg</p>"; ?>

<table>
    <tr>
        <th>User</th>
        <th>Task</th>
        <th>Answer</th>
        <th>Auto Score</th>
        <th>Admin Score</th>
        <th>Update</th>
    </tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['display_name'] ?? "Unknown User") ?></td>
    <td><?= htmlspecialchars($row['task_description']) ?></td>
    <td><?= htmlspecialchars($row['answer_text']) ?></td>
    <td><?= $row['auto_score'] ?? 0 ?></td>
    <td><?= $row['admin_score'] ?? "Not graded" ?></td>
    <td>
        <form method="POST">
            <input type="hidden" name="answer_id" value="<?= $row['id'] ?>">
            <input type="number" name="admin_score" required min="0" max="100">
            <button type="submit">Save</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>