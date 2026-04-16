<?php
session_start();

$conn = new mysqli("localhost","root","","skills_gap_db");
if ($conn->connect_error) die("DB error");

$user_id = $_SESSION["user_id"];
$skill_id = $_GET["skill_id"];

$q = $conn->prepare("SELECT * FROM skill_questions WHERE skill_id=?");
$q->bind_param("i",$skill_id);
$q->execute();
$result = $q->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Skill Test</title>
    <style>
        body { font-family:Arial; background:#f4f6f8; }
        .box {
            background:#fff;
            max-width:700px;
            margin:40px auto;
            padding:20px;
        }
        button {
            background:#28a745;
            color:#fff;
            padding:10px;
            border:none;
            margin-top:10px;
        }
    </style>
</head>
<body>

<div class="box">
<h2>Skill Test</h2>

<form action="submit_test.php" method="POST">
<input type="hidden" name="skill_id" value="<?= $skill_id ?>">

<?php while($row=$result->fetch_assoc()): ?>
<p><strong><?= $row["question"] ?></strong></p>

<label><input type="radio" name="q<?= $row["id"] ?>" value="a"> <?= $row["option_a"] ?></label><br>
<label><input type="radio" name="q<?= $row["id"] ?>" value="b"> <?= $row["option_b"] ?></label><br>
<label><input type="radio" name="q<?= $row["id"] ?>" value="c"> <?= $row["option_c"] ?></label><br>
<label><input type="radio" name="q<?= $row["id"] ?>" value="d"> <?= $row["option_d"] ?></label><br>

<hr>
<?php endwhile; ?>

<button type="submit">Submit Test</button>
</form>
</div>

</body>
</html>
