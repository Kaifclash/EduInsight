<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: ../login.html");
  exit;
}

$success = "";
$error   = "";

/* ---------- ADD / DELETE HANDLING ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  // ADD
  if ($action === "add") {
    $classNameRaw = trim($_POST["class_name"] ?? "");
    $className = strtoupper(preg_replace('/\s+/', ' ', $classNameRaw)); // normalize spaces + uppercase

    if ($className === "") {
      $error = "Class name is required.";
    } elseif (strlen($className) > 50) {
      $error = "Class name too long (max 50 chars).";
    } else {
      try {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name) VALUES (?)");
        $stmt->execute([$className]);
        $success = "Class added: {$className}";
      } catch (PDOException $e) {
        // Duplicate unique class_name
        $error = "This class already exists.";
      }
    }
  }

  // DELETE
  if ($action === "delete") {
    $id = (int)($_POST["class_id"] ?? 0);
    if ($id > 0) {
      $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
      $stmt->execute([$id]);
      $success = "Class deleted successfully.";
    } else {
      $error = "Invalid class selected.";
    }
  }
}

/* ---------- FETCH CLASSES ---------- */
$classes = $pdo->query("SELECT class_id, class_name, created_at FROM classes ORDER BY class_name ASC")->fetchAll();
$totalClasses = count($classes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduInsight | Manage Classes</title>

  <style>
    :root{
      --bg:#070b1a;
      --text:#e9eeff;
      --muted:#a9b2d6;
      --accent:#7c5cff;
      --accent2:#00d4ff;
      --good:#2dff9a;
      --danger:#ff4d6d;
      --border:rgba(255,255,255,.12);
      --shadow: 0 20px 70px rgba(0,0,0,.45);
      --radius:18px;
    }
    *{box-sizing:border-box;}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      color:var(--text);
      background:
        radial-gradient(1200px 600px at 15% 10%, rgba(124,92,255,.25), transparent 55%),
        radial-gradient(900px 500px at 85% 20%, rgba(0,212,255,.20), transparent 55%),
        linear-gradient(180deg, #060816 0%, var(--bg) 70%, #040514 100%);
      min-height:100vh;
      overflow-x:hidden;
    }

    .wrap{
      width:100%;
      max-width:1100px;
      margin: 18px auto;
      padding: 0 16px;
    }

    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      border:1px solid var(--border);
      border-radius: var(--radius);
      background: rgba(255,255,255,.04);
      box-shadow: var(--shadow);
      padding:16px;
    }

    .title{
      display:flex;
      gap:12px;
      align-items:center;
    }
    .logo{
      width:44px; height:44px;
      border-radius:14px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      box-shadow: 0 10px 30px rgba(124,92,255,.25);
    }
    .title h1{
      margin:0;
      font-size:18px;
      letter-spacing:.2px;
    }
    .title p{
      margin:6px 0 0;
      color:var(--muted);
      font-weight:700;
      font-size:13px;
    }

    .btn{
      border:1px solid var(--border);
      background: rgba(255,255,255,.06);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      font-weight:900;
      cursor:pointer;
      transition:.18s ease;
      display:inline-flex;
      gap:10px;
      align-items:center;
      justify-content:center;
      user-select:none;
      text-decoration:none;
      white-space:nowrap;
    }
    .btn:hover{transform: translateY(-1px); background: rgba(255,255,255,.10);}
    .btn-primary{
      border:none;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      box-shadow: 0 18px 50px rgba(124,92,255,.25);
    }
    .btn-danger{
      border:1px solid rgba(255,77,109,.35);
      background: rgba(255,77,109,.10);
      color:#ffd1d9;
    }

    .grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:14px;
      margin-top:14px;
      align-items:start;
    }

    .card{
      border:1px solid var(--border);
      border-radius: var(--radius);
      background: rgba(255,255,255,.04);
      box-shadow: 0 18px 50px rgba(0,0,0,.22);
      padding:16px;
    }

    .kpi{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:12px;
      padding-bottom:12px;
      border-bottom:1px solid var(--border);
    }
    .kpi .label{color:var(--muted); font-weight:900; font-size:12px;}
    .kpi .value{font-size:26px; font-weight:1000;}

    .msg{
      display:none;
      margin-top:12px;
      padding:10px 12px;
      border-radius:14px;
      font-weight:850;
      font-size:13px;
      line-height:1.4;
    }
    .msg.ok{display:block; border:1px solid rgba(45,255,154,.25); background: rgba(45,255,154,.10); color:#d6ffe9;}
    .msg.err{display:block; border:1px solid rgba(255,77,109,.35); background: rgba(255,77,109,.10); color:#ffd1d9;}

    label{display:block; font-weight:900; font-size:13px; margin-bottom:6px;}
    .field{
      border:1px solid var(--border);
      background: rgba(255,255,255,.05);
      border-radius:14px;
      padding:10px 12px;
    }
    input{
      width:100%;
      border:none; outline:none;
      background:transparent;
      color:var(--text);
      font-size:14px;
      font-weight:800;
    }
    input::placeholder{color: rgba(169,178,214,.65); font-weight:650;}

    .help{margin-top:8px; color:var(--muted); font-size:12px; font-weight:650; line-height:1.4;}

    table{
      width:100%;
      border-collapse:separate;
      border-spacing:0 10px;
      margin-top:12px;
    }
    th{
      text-align:left;
      color:var(--muted);
      font-size:12px;
      font-weight:900;
      padding:0 10px 8px;
    }
    td{
      padding:12px 10px;
      background: rgba(11,18,42,.35);
      border-top:1px solid var(--border);
      border-bottom:1px solid var(--border);
      font-weight:850;
      color:var(--text);
    }
    tr td:first-child{
      border-left:1px solid var(--border);
      border-top-left-radius:14px;
      border-bottom-left-radius:14px;
    }
    tr td:last-child{
      border-right:1px solid var(--border);
      border-top-right-radius:14px;
      border-bottom-right-radius:14px;
    }

    .row-actions{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      align-items:center;
    }

    @media (max-width: 900px){
      .grid{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>

<div class="wrap">
  <div class="topbar">
    <div class="title">
      <div class="logo"></div>
      <div>
        <h1>Manage Classes</h1>
        <p>Create FY/SY/TY or any custom classes for EduInsight.</p>
      </div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="admin_dashboard.php">← Dashboard</a>
      <a class="btn btn-danger" href="../logout.php">Logout</a>
    </div>
  </div>

  <?php if($success): ?>
    <div class="msg ok" id="flashMsg"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if($error): ?>
    <div class="msg err" id="flashMsg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="grid">
    <!-- Add Class -->
    <div class="card">
      <div class="kpi">
        <div>
          <div class="label">TOTAL CLASSES</div>
          <div class="value"><?= (int)$totalClasses ?></div>
        </div>
        <div class="label">Module: Step 1</div>
      </div>

      <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="add" />
        <label for="class_name">Class Name</label>
        <div class="field">
          <input id="class_name" name="class_name" type="text" placeholder="FY / SY / TY / BCA-SEM-3" />
        </div>
        <div class="help">Tip: We auto-format to UPPERCASE and remove extra spaces.</div>

        <button class="btn btn-primary" type="submit" style="width:100%; margin-top:12px;">
          + Add Class
        </button>
      </form>
    </div>

    <!-- List Classes -->
    <div class="card">
      <div class="kpi">
        <div>
          <div class="label">CLASS LIST</div>
          <div class="value"><?= (int)$totalClasses ?></div>
        </div>
        <div class="label">A-Z</div>
      </div>

      <?php if($totalClasses === 0): ?>
        <div class="help" style="font-size:13px;">
          No classes found. Add your first class from the left panel.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Class</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($classes as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c["class_name"]) ?></td>
                <td style="text-align:right;">
                  <div class="row-actions">
                    <form method="POST" onsubmit="return confirm('Delete this class?');" style="margin:0;">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="class_id" value="<?= (int)$c["class_id"] ?>" />
                      <button class="btn btn-danger" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  // Auto-hide success/error message after 2.5s
  const flash = document.getElementById("flashMsg");
  if (flash) {
    setTimeout(() => flash.style.display = "none", 2500);
  }
</script>

</body>
</html>
