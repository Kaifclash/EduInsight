<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
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

/* ---------- ADD SUBJECT (PRG) ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject = trim($_POST["subject_name"] ?? "");
    $classId = (int)($_POST["class_id"] ?? 0);

    if ($subject === "" || $classId <= 0) {
        header("Location: subjects.php?req=1");
        exit;
    }

    // Optional: normalize subject name spacing
    $subject = preg_replace('/\s+/', ' ', $subject);

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

/* ---------- DELETE SUBJECT (PRG) ---------- */
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->execute([$id]);
    header("Location: subjects.php?del=1");
    exit;
}

/* ---------- FETCH DATA ---------- */
$classes = $pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetchAll();

$subjects = $pdo->query("
    SELECT s.subject_id, s.subject_name, c.class_name
    FROM subjects s
    JOIN classes c ON s.class_id = c.class_id
    ORDER BY c.class_name, s.subject_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>EduInsight | Manage Subjects</title>

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

  a{color:inherit;text-decoration:none;}

  .wrap{
    width:100%;
    max-width: 980px;
    margin: 18px auto;
    padding: 0 16px;
  }

  .topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    border:1px solid var(--border);
    border-radius: var(--radius);
    background: rgba(255,255,255,.04);
    box-shadow: var(--shadow);
    padding:16px;
  }

  .title{
    display:flex; gap:12px; align-items:center;
  }
  .logo{
    width:44px; height:44px;
    border-radius:14px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    box-shadow: 0 10px 30px rgba(124,92,255,.25);
  }
  h1{
    margin:0;
    font-size:18px;
  }
  .sub{
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

  .card{
    margin-top:14px;
    border:1px solid var(--border);
    border-radius: var(--radius);
    background: rgba(255,255,255,.04);
    box-shadow: 0 18px 50px rgba(0,0,0,.22);
    padding:16px;
  }

  /* ✅ FIX INPUT / SELECT LOOK */
  label{
    display:block;
    font-weight:900;
    font-size:13px;
    margin: 8px 0 6px;
    color:#d9e2ff;
  }

  .field{
    border:1px solid var(--border);
    background: rgba(255,255,255,.05);
    border-radius:14px;
    padding:10px 12px;
    transition:.18s ease;
  }
  .field:focus-within{
    border-color: rgba(0,212,255,.55);
    box-shadow: 0 0 0 4px rgba(0,212,255,.10);
  }

  input, select{
    width:100%;
    border:none;
    outline:none;
    background:transparent;
    color:var(--text);
    font-size:14px;
    font-weight:800;
    padding: 2px 0;
  }

  input::placeholder{color: rgba(169,178,214,.65); font-weight:650;}

  /* Select dropdown options (browser dependent, but helps) */
  select option{
    background:#0b1020;
    color:var(--text);
  }

  .row{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:12px;
  }
  @media (max-width: 700px){
    .row{grid-template-columns:1fr;}
  }

  .msg{
    margin-top:14px;
    padding:10px 12px;
    border-radius:14px;
    font-weight:850;
    font-size:13px;
    line-height:1.4;
  }
  .msg.success{border:1px solid rgba(45,255,154,.25); background: rgba(45,255,154,.10); color:#d6ffe9;}
  .msg.error{border:1px solid rgba(255,77,109,.35); background: rgba(255,77,109,.10); color:#ffd1d9;}

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
</style>
</head>
<body>

<div class="wrap">
  <div class="topbar">
    <div class="title">
      <div class="logo"></div>
      <div>
        <h1>Manage Subjects</h1>
        <div class="sub">Add subjects class-wise (FY/SY/TY…)</div>
      </div>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="admin_dashboard.php">← Dashboard</a>
      <a class="btn btn-danger" href="../logout.php">Logout</a>
    </div>
  </div>

  <?php if($error): ?>
    <div class="msg error" id="flashMsg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if($success): ?>
    <div class="msg success" id="flashMsg"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" autocomplete="off">
      <div class="row">
        <div>
          <label>Select Class</label>
          <div class="field">
            <select name="class_id" required>
              <option value="">Choose a class</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= (int)$c["class_id"] ?>"><?= htmlspecialchars($c["class_name"]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label>Subject Name</label>
          <div class="field">
            <input type="text" name="subject_name" placeholder="e.g., DBMS / C++ / Web Tech" required />
          </div>
        </div>
      </div>

      <button class="btn btn-primary" type="submit" style="width:100%; margin-top:12px;">
        + Add Subject
      </button>
    </form>

    <table>
      <thead>
        <tr>
          <th>Class</th>
          <th>Subject</th>
          <th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($subjects)): ?>
          <tr>
            <td colspan="3">No subjects added yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach($subjects as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s["class_name"]) ?></td>
              <td><?= htmlspecialchars($s["subject_name"]) ?></td>
              <td style="text-align:right;">
                <a class="btn btn-danger"
                   href="?delete=<?= (int)$s["subject_id"] ?>"
                   onclick="return confirm('Delete this subject?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
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
