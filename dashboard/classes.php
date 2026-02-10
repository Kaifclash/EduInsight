<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

$error = "";
$success = "";

/* Add class */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_name = trim($_POST["class_name"] ?? "");

    if ($class_name === "") {
        $error = "Class name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (class_name) VALUES (?)");
            $stmt->execute([$class_name]);
            $success = "Class added successfully.";
        } catch (PDOException $e) {
            $error = "Class already exists.";
        }
    }
}

/* Fetch classes */
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Classes</title>
  <style>
    body{font-family:Arial;background:#0b1020;color:#fff;padding:20px}
    .box{max-width:500px;margin:auto;background:#121a3a;padding:20px;border-radius:10px}
    input,button{width:100%;padding:10px;margin-top:10px}
    button{background:#7c5cff;color:#fff;border:none;font-weight:bold;cursor:pointer}
    .msg{margin:10px 0;font-weight:bold}
    table{width:100%;margin-top:20px;border-collapse:collapse}
    td,th{padding:10px;border-bottom:1px solid #333}
    a{color:#ff4d6d;text-decoration:none}
  </style>
</head>
<body>

<div class="box">
  <h2>Manage Classes</h2>

  <?php if($error): ?><div class="msg" style="color:#ff4d6d"><?= $error ?></div><?php endif; ?>
  <?php if($success): ?><div class="msg" style="color:#2dff9a"><?= $success ?></div><?php endif; ?>

  <form method="POST">
    <input type="text" name="class_name" placeholder="Enter class (FY / SY / TY)" />
    <button type="submit">Add Class</button>
  </form>

  <table>
    <tr>
      <th>Class</th>
      <th>Action</th>
    </tr>
    <?php foreach($classes as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c["class_name"]) ?></td>
      <td>
        <a href="delete_class.php?id=<?= $c["class_id"] ?>" onclick="return confirm('Delete this class?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <br>
  <a href="admin_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
