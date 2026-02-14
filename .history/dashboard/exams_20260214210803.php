<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: ../login.html");
  exit;
}

$success = "";
$error = "";

/* flash */
if (isset($_GET["ok"]))  $success = "Exam added successfully.";
if (isset($_GET["del"])) $success = "Exam deleted successfully.";
if (isset($_GET["req"])) $error   = "Exam name is required.";
if (isset($_GET["dup"])) $error   = "Exam already exists.";

/* add */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";
  if ($action === "add") {
    $name = trim($_POST["exam_name"] ?? "");
    $date = $_POST["exam_date"] ?? null;

    if ($name === "") { header("Location: exams.php?req=1"); exit; }

    try {
      $stmt = $pdo->prepare("INSERT INTO exams (exam_name, exam_date) VALUES (?, ?)");
      $stmt->execute([$name, $date ?: null]);
      header("Location: exams.php?ok=1"); exit;
    } catch (PDOException $e) {
      header("Location: exams.php?dup=1"); exit;
    }
  }

  if ($action === "delete") {
    $id = (int)($_POST["exam_id"] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare("DELETE FROM exams WHERE exam_id = ?");
      $stmt->execute([$id]);
      header("Location: exams.php?del=1"); exit;
    }
  }
}

$exams = $pdo->query("SELECT exam_id, exam_name, exam_date FROM exams ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>EduInsight | Exams</title>
<style>
  :root{--bg:#070b1a;--text:#e9eeff;--muted:#a9b2d6;--accent:#7c5cff;--accent2:#00d4ff;--danger:#ff4d6d;--good:#2dff9a;--border:rgba(255,255,255,.12);--radius:18px;}
  *{box-sizing:border-box;}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;color:var(--text);
    background:radial-gradient(900px 500px at 20% 10%, rgba(124,92,255,.25), transparent 55%),
             radial-gradient(900px 500px at 80% 20%, rgba(0,212,255,.20), transparent 55%),
             linear-gradient(180deg,#060816,var(--bg));
    min-height:100vh; padding:18px;}
  .wrap{max-width:950px;margin:0 auto;}
  .top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;
    border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .btn{border:1px solid var(--border);background:rgba(255,255,255,.06);color:var(--text);padding:10px 12px;border-radius:12px;font-weight:900;cursor:pointer;text-decoration:none;}
  .btn-primary{border:none;background:linear-gradient(135deg,var(--accent),var(--accent2));}
  .btn-danger{border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.10);color:#ffd1d9;}
  .card{margin-top:14px;border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .msg{margin-top:12px;padding:10px 12px;border-radius:14px;font-weight:850;font-size:13px;}
  .ok{border:1px solid rgba(45,255,154,.25);background:rgba(45,255,154,.10);color:#d6ffe9;}
  .err{border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.10);color:#ffd1d9;}
  label{display:block;font-weight:900;font-size:13px;margin:10px 0 6px;}
  .field{border:1px solid var(--border);background:rgba(255,255,255,.05);border-radius:14px;padding:10px 12px;}
  input{width:100%;border:none;outline:none;background:transparent;color:var(--text);font-weight:800;}
  table{width:100%;margin-top:12px;border-collapse:separate;border-spacing:0 10px;}
  th{color:var(--muted);font-size:12px;font-weight:900;text-align:left;padding:0 10px 8px;}
  td{padding:12px 10px;background:rgba(11,18,42,.35);border-top:1px solid var(--border);border-bottom:1px solid var(--border);font-weight:850;}
  tr td:first-child{border-left:1px solid var(--border);border-top-left-radius:14px;border-bottom-left-radius:14px;}
  tr td:last-child{border-right:1px solid var(--border);border-top-right-radius:14px;border-bottom-right-radius:14px;text-align:right;}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <b style="font-size:18px;">Exams</b>
      <div style="color:var(--muted);font-weight:700;font-size:13px;margin-top:4px;">Create exams like Mid/Final/Unit Test</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="admin_dashboard.php">← Dashboard</a>
      <a class="btn btn-danger" href="../logout.php">Logout</a>
    </div>
  </div>

  <?php if($success): ?><div class="msg ok" id="flashMsg"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="msg err" id="flashMsg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <input type="hidden" name="action" value="add" />
      <label>Exam Name</label>
      <div class="field"><input name="exam_name" placeholder="e.g., Mid Sem - 1" required></div>

      <label>Exam Date (optional)</label>
      <div class="field"><input type="date" name="exam_date"></div>

      <button class="btn btn-primary" type="submit" style="width:100%; margin-top:12px;">+ Add Exam</button>
    </form>

    <table>
      <thead>
        <tr><th>Exam</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php if(empty($exams)): ?>
        <tr><td colspan="3">No exams yet.</td></tr>
      <?php else: foreach($exams as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e["exam_name"]) ?></td>
          <td><?= htmlspecialchars($e["exam_date"] ?? "-") ?></td>
          <td>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this exam?');">
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="exam_id" value="<?= (int)$e["exam_id"] ?>" />
              <button class="btn btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  const flash = document.getElementById("flashMsg");
  if (flash) setTimeout(() => flash.style.display = "none", 2500);
</script>
</body>
</html>
