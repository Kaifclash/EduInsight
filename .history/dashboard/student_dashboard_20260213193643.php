<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "student") {
    header("Location: ../login.html");
    exit;
}

$user_id = $_SESSION["user_id"];

/* ---------- FETCH STUDENT INFO ---------- */
$stmt = $pdo->prepare("
    SELECT u.full_name, s.roll_no, c.class_name, c.class_id
    FROM students s
    JOIN users u ON u.user_id = s.user_id
    JOIN classes c ON c.class_id = s.class_id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}

/* ---------- FETCH SUBJECTS ---------- */
$stmt2 = $pdo->prepare("
    SELECT subject_name
    FROM subjects
    WHERE class_id = ?
    ORDER BY subject_name ASC
");
$stmt2->execute([$student["class_id"]]);
$subjects = $stmt2->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>EduInsight | Student Dashboard</title>
<style>
:root{
  --bg:#070b1a;
  --text:#e9eeff;
  --muted:#a9b2d6;
  --accent:#7c5cff;
  --accent2:#00d4ff;
  --border:rgba(255,255,255,.12);
  --radius:18px;
}
body{
  margin:0;
  font-family:Arial;
  background:
    radial-gradient(1200px 600px at 15% 10%, rgba(124,92,255,.25), transparent 55%),
    linear-gradient(180deg, #060816 0%, var(--bg) 70%, #040514 100%);
  color:var(--text);
  min-height:100vh;
}
.wrap{
  max-width:900px;
  margin:20px auto;
  padding:0 16px;
}
.card{
  background:rgba(255,255,255,.05);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:20px;
  margin-top:16px;
}
h1{
  margin:0;
  font-size:22px;
}
.sub{
  color:var(--muted);
  margin-top:6px;
}
.btn{
  display:inline-block;
  padding:8px 12px;
  border-radius:12px;
  font-weight:bold;
  text-decoration:none;
  margin-top:10px;
}
.logout{
  background:#ff4d6d;
  color:white;
}
ul{
  margin-top:12px;
  padding-left:18px;
}
li{
  margin-bottom:6px;
}
</style>
</head>
<body>

<div class="wrap">

  <div class="card">
    <h1>Welcome, <?= htmlspecialchars($student["full_name"]) ?> 👋</h1>
    <div class="sub">
      Class: <?= htmlspecialchars($student["class_name"]) ?> |
      Roll No: <?= htmlspecialchars($student["roll_no"]) ?>
    </div>
    <a class="btn logout" href="../logout.php">Logout</a>
  </div>

  <div class="card">
    <h2>Your Subjects</h2>

    <?php if(empty($subjects)): ?>
        <p>No subjects assigned yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach($subjects as $s): ?>
                <li><?= htmlspecialchars($s["subject_name"]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
  </div>

</div>

</body>
</html>