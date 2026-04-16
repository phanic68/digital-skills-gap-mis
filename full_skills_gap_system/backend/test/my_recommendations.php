<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    die("Please login first");
}

$conn = new mysqli("localhost", "root", "", "skills_gap_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

$user_id = $_SESSION["user_id"];

/*
Tables used:
- skills(id, skill_name)
- skill_levels(id, skill_id, level_name, learning_resource)
- user_skill_progress(user_id, skill_id, current_level)
*/

// Helper function: get next level
function getNextLevel($current) {
    if ($current == "Beginner") return "Intermediate";
    if ($current == "Intermediate") return "Advanced";
    if ($current == "Advanced") return "Mastered";
    return "Beginner"; // if None or not started
}

// Fetch all skills with user's progress
$skillsQuery = "
SELECT s.id AS skill_id, s.skill_name,
       COALESCE(usp.current_level,'None') AS user_level
FROM skills s
LEFT JOIN user_skill_progress usp 
    ON usp.skill_id = s.id AND usp.user_id = ?
ORDER BY s.skill_name
";

$stmt = $conn->prepare($skillsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$skills = [];
while ($row = $result->fetch_assoc()) {
    $skills[$row['skill_id']] = $row;
}
$stmt->close();

// Fetch all skill levels with learning resources
$levelsQuery = "SELECT * FROM skill_levels";
$levelsResult = $conn->query($levelsQuery);

$skillLevels = [];
while ($row = $levelsResult->fetch_assoc()) {
    $skillLevels[$row['skill_id']][$row['level_name']] = $row['learning_resource'];
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Learning Recommendations</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:20px; }
        .box { background:#fff; max-width:800px; margin:auto; padding:20px; border-radius:6px; }
        h2 { text-align:center; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th, td { padding:10px; border-bottom:1px solid #ccc; text-align:left; }
        th { background:#007bff; color:#fff; }
        a { color:#007bff; text-decoration:none; }
        .level-badge { padding:3px 8px; border-radius:4px; color:white; font-size:12px; }
        .Beginner { background:#28a745; }
        .Intermediate { background:#ffc107; color:black; }
        .Advanced { background:#dc3545; }
        .Mastered { background:#6c757d; }
        .no-resource { color:#999; font-style:italic; }
    </style>
</head>
<body>

<div class="box">
<h2>My Learning Recommendations</h2>

<?php if (empty($skills)): ?>
    <p style="text-align:center; color:green;">
        🎉 You have no skills yet! Start learning now.
    </p>
<?php else: ?>
<table>
<tr>
    <th>Skill</th>
    <th>Your Level</th>
    <th>Next Level</th>
    <th>Learning Resource</th>
</tr>

<?php foreach($skills as $skill): 
    $current = $skill['user_level'];
    $nextLevel = ($current == "Mastered") ? "Mastered" : getNextLevel($current);
    $resource = $skillLevels[$skill['skill_id']][$nextLevel] ?? null;
?>
<tr>
    <td><?= htmlspecialchars($skill['skill_name']) ?></td>
    <td><?= htmlspecialchars($current) ?></td>
    <td>
        <span class="level-badge <?= $nextLevel ?>">
            <?= $nextLevel ?>
        </span>
    </td>
    <td>
        <?php if($resource): ?>
            <a href="<?= htmlspecialchars($resource) ?>" target="_blank">Start Learning</a>
        <?php else: ?>
            <span class="no-resource">No recommendation available</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<br>
<a href="../../dashboard.html">← Back to Dashboard</a>
</div>

</body>
</html>