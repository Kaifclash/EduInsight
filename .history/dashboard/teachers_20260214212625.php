<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: ../login.html");
  exit;
}

/* flash */
$success=""; $error="";
if (isset($_GET["ok"]))  $success="Teacher added successfully.";
if (isset($_GET["del"])) $success="Teacher deleted successfully.";
if (isset($_GET["req"])) $error="All required fields must be filled.";
if (isset($_GET["dup"])) $error="Email already exists.";
if (isset($_GET["fail"])) $error="Something went wrong.";

/* actions */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  // add teacher
  if ($action === "add") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    $password  = $_POST["password"] ?? "";

    if ($full_name==="" || $email==="" || $password==="") { header("Location: teachers.php?req=1"); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { header("Location: teachers.php?req=1"); exit; }
    if (strlen($password) < 8) { header("Location: teachers.php?req=1"); exit; }

    try {
      $pdo->beginTransaction();

      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users (role, full_name, email, phone, password_hash) VALUES ('teacher', ?, ?, ?, ?)");
      $stmt->execute([$full_name, $email, $phone, $hash]);
      $user_id = (int)$pdo->lastInsertId();

      $stmt2 = $pdo->prepare("INSERT INTO teachers (user_id) VALUES (?)");
      $stmt2->execute([$user_id]);

      $pdo->commit();
      header("Location: teachers.php?ok=1"); exit;

    } catch (PDOException $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      header("Location: teachers.php?dup=1"); exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      header("Location: teachers.php?fail=1"); exit;
    }
  }

  // delete teacher
  if ($action === "delete") {
    $user_id = (int)($_POST["user_id"] ?? 0);
    if ($user_id > 0) {
      $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role='teacher'");
      $stmt->execute([$user_id]);
      header("Location: teachers.php?del=1"); exit;
    }
    header("Location: teachers.php?fail=1"); exit;
  }
}

/* fetch */
$teachers = $pdo->query("
  SELECT u.user_id, u.full_name, u.email, u.phone
  FROM teachers t
  JOIN users u ON u.user_id=t.user_id
  ORDER BY u.full_name
")->fetchAll();
$total = count($teachers);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>EduInsight | Manage Teachers</title>
<style>
  :root{--bg:#070b1a;--text:#e9eeff;--muted:#a9b2d6;--accent:#7c5cff;--accent2:#00d4ff;--danger:#ff4d6d;--good:#2dff9a;--border:rgba(255,255,255,.12);--radius:18px;}
  *{box-sizing:border-box;}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;color:var(--text);
    background:radial-gradient(900px 500px at 20% 10%, rgba(124,92,255,.25), transparent 55%),
             radial-gradient(900px 500px at 80% 20%, rgba(0,212,255,.20), transparent 55%),
             linear-gradient(180deg,#060816,var(--bg));
    min-height:100vh;}
  .wrap{max-width:1100px;margin:18px auto;padding:0 16px;}
  .topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;
    border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .btn{border:1px solid var(--border);background:rgba(255,255,255,.06);color:var(--text);
    padding:10px 12px;border-radius:12px;font-weight:900;cursor:pointer;text-decoration:none;display:inline-flex;gap:8px;}
  .btn-primary{border:none;background:linear-gradient(135deg,var(--accent),var(--accent2));}
  .btn-danger{border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.10);color:#ffd1d9;}
  .grid{display:grid;grid-template-columns:1fr 1.3fr;gap:14px;margin-top:14px;}
  .card{border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .kpi{display:flex;justify-content:space-between;align-items:center;padding-bottom:12px;border-bottom:1px solid var(--border);}
  .kpi .label{color:var(--muted);font-weight:900;font-size:12px;}
  .kpi .value{font-size:26px;font-weight:1000;}
  .msg{margin-top:14px;padding:10px 12px;border-radius:14px;font-weight:850;font-size:13px;}
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
  @media(max-width:950px){.grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <div>
      <b style="font-size:18px;">Manage Teachers</b>
      <div style="color:var(--muted);font-weight:700;font-size:13px;margin-top:4px;">Create teacher accounts for marks entry</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="admin_dashboard.php">← Dashboard</a>
      <a class="btn btn-danger" href="../logout.php">Logout</a>
    </div>
  </div>

  <?php if($success): ?><div class="msg ok" id="flashMsg"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="msg err" id="flashMsg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <div class="kpi">
        <div><div class="label">TOTAL TEACHERS</div><div class="value"><?= (int)$total ?></div></div>
        <div class="label">Setup</div>
      </div>

      <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="add" />

        <label>Full Name</label>
        <div class="field"><input name="full_name" placeholder="e.g., Prof. Rahim" required></div>

        <label>Email</label>
        <div class="field"><input name="email" type="email" placeholder="teacher@school.com" required></div>

        <label>Phone (optional)</label>
        <div class="field"><input name="phone" placeholder="10-digit"></div>

        <label>Password (min 8)</label>
        <div class="field"><input name="password" type="password" placeholder="Create password" required></div>

        <button class="btn btn-primary" type="submit" style="width:100%;margin-top:12px;">+ Add Teacher</button>
      </form>
    </div>

    <div class="card">
      <div class="kpi">
        <div><div class="label">TEACHER LIST</div><div class="value"><?= (int)$total ?></div></div>
        <div class="label">A-Z</div>
      </div>

      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($teachers)): ?>
          <tr><td colspan="3">No teachers yet.</td></tr>
        <?php else: foreach($teachers as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t["full_name"]) ?></td>
            <td><?= htmlspecialchars($t["email"]) ?></td>
            <td>
              <form method="POST" style="margin:0" onsubmit="return confirm('Delete this teacher account?');">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="user_id" value="<?= (int)$t["user_id"] ?>" />
                <button class="btn btn-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const flash = document.getElementById("flashMsg");
  if (flash) setTimeout(() => flash.style.display = "none", 2500);
</script>
</body>
</html>