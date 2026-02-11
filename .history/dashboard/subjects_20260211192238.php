<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

$success = "";
$error   = "";

/* ---------- FLASH MESSAGE (GET) ---------- */
if (isset($_GET["ok"]))  $success = "Subject added successfully.";
if (isset($_GET["dup"])) $error   = "Subject already exists for this class.";
if (isset($_GET["req"])) $error   = "Subject and Class are required.";
if (isset($_GET["del"])) $success = "Subject deleted successfully.";

/* ---------- ADD SUBJECT ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject = trim($_POST["subject_name"] ?? "");
    $classId = (int)($_POST["class_id"] ?? 0);

    if ($subject === "" || $classId <= 0) {
        header("Location: subjects.php?req=1");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, class_id) VALUES (?, ?)");
        $stmt->execute([$subject, $classId]);

        header("Location: subjects.php?ok=1");
        exit;

    } catch (PDOException $e) {
        header("Location: subjects.php?dup=1");
        exit;
    }
}

/* ---------- DELETE SUBJECT ---------- */
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->execute([$id]);

    header("Location: subjects.php?del=1");
    exit;
}

/* ---------- FETCH DATA ---------- */
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC")->fetchAll();

$subjects = $pdo->query("
    SELECT s.subject_id, s.subject_name, c.class_name
    FROM subjects s
    JOIN classes c ON s.class_id = c.class_id
    ORDER BY c.class_name, s.subject_name
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Subjects</title>
<style>
body{
    background:#0b1020;
    color:#fff;
    font-family:Arial;
    padding:20px;
}
.box{
    max-width:800px;
    margin:auto;
    background:#121a3a;
    padding:20px;
    border-radius:12px;
}
input,select,button{
    width:100%;
    padding:10px;
    margin-top:10px;
}
button{
    background:#7c5cff;
    border:none;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
}
button:hover{
    opacity:0.9;
}
table{
    width:100%;
    margin-top:20px;
    border-collapse:collapse;
}
td,th{
    padding:10px;
    border-bottom:1px solid #333;
}
a{
    color:#ff4d6d;
    text-decoration:none;
}
.msg{
    margin-top:10px;
    font-weight:bold;
}
.success{ color:#2dff9a; }
.error{ color:#ff4d6d; }
.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}
.top a{
    color:#00d4ff;
    text-decoration:none;
    font-weight:bold;
}
</style>
</head>
<body>

<div class="box">

<div class="top">
    <h2>Manage Subjects</h2>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
</div>

<?php if($error): ?>
    <div class="msg error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if($success): ?>
    <div class="msg success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- ADD FORM -->
<form method="POST" autocomplete="off">
    <select name="class_id">
        <option value="">Select Class</option>
        <?php foreach($classes as $c): ?>
            <option value="<?= $c["class_id"] ?>">
                <?= htmlspecialchars($c["class_name"]) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="subject_name" placeholder="Enter Subject Name" />
    <button type="submit">Add Subject</button>
</form>

<!-- SUBJECT LIST -->
<table>
<tr>
    <th>Class</th>
    <th>Subject</th>
    <th>Action</th>
</tr>

<?php if(empty($subjects)): ?>
<tr>
    <td colspan="3">No subjects added yet.</td>
</tr>
<?php else: ?>

<?php foreach($subjects as $s): ?>
<tr>
    <td><?= htmlspecialchars($s["class_name"]) ?></td>
    <td><?= htmlspecialchars($s["subject_name"]) ?></td>
    <td>
        <a href="?delete=<?= $s["subject_id"] ?>" 
           onclick="return confirm('Delete this subject?')">
           Delete
        </a>
    </td>
</tr>
<?php endforeach; ?>

<?php endif; ?>
</table>

</div>

<script>
// Auto hide message after 2.5 seconds
const msg = document.querySelector(".msg");
if (msg) {
    setTimeout(() => {
        msg.style.display = "none";
    }, 2500);
}
</script>

</body>
</html>
