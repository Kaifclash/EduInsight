<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

$success = "";
$error = "";

/* ADD SUBJECT */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject = trim($_POST["subject_name"] ?? "");
    $classId = (int)($_POST["class_id"] ?? 0);

    if ($subject === "" || $classId <= 0) {
        $error = "Subject and Class are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, class_id) VALUES (?, ?)");
            $stmt->execute([$subject, $classId]);
            $success = "Subject added successfully.";
        } catch (PDOException $e) {
            $error = "Subject already exists for this class.";
        }
    }
}

/* DELETE SUBJECT */
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->execute([$id]);
    header("Location: subjects.php");
    exit;
}

/* FETCH DATA */
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
body{background:#0b1020;color:#fff;font-family:Arial;padding:20px}
.box{max-width:700px;margin:auto;background:#121a3a;padding:20px;border-radius:12px}
input,select,button{width:100%;padding:10px;margin-top:10px}
button{background:#7c5cff;border:none;color:#fff;font-weight:bold}
table{width:100%;margin-top:20px;border-collapse:collapse}
td,th{padding:10px;border-bottom:1px solid #333}
a{color:#ff4d6d;text-decoration:none}
.msg{margin-top:10px;font-weight:bold}
</style>
</head>
<body>

<div class="box">
<h2>Manage Subjects</h2>

<?php if($error): ?><div class="msg" style="color:red"><?= $error ?></div><?php endif; ?>
<?php if($success): ?><div class="msg" style="color:#2dff9a"><?= $success ?></div><?php endif; ?>

<form method="POST">
    <select name="class_id">
        <option value="">Select Class</option>
        <?php foreach($classes as $c): ?>
            <option value="<?= $c["class_id"] ?>"><?= $c["class_name"] ?></option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="subject_name" placeholder="Enter Subject Name" />
    <button type="submit">Add Subject</button>
</form>

<table>
<tr>
    <th>Class</th>
    <th>Subject</th>
    <th>Action</th>
</tr>
<?php foreach($subjects as $s): ?>
<tr>
    <td><?= $s["class_name"] ?></td>
    <td><?= $s["subject_name"] ?></td>
    <td>
        <a href="?delete=<?= $s["subject_id"] ?>" onclick="return confirm('Delete subject?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</table>

<br>
<a href="admin_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
