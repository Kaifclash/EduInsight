<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: ../login.html");
  exit;
}

/* ---------- FLASH (GET) ---------- */
$success = "";
$error = "";
if (isset($_GET["ok"]))  $success = "Student added successfully.";
if (isset($_GET["del"])) $success = "Student deleted successfully.";
if (isset($_GET["req"])) $error   = "All required fields must be filled.";
if (isset($_GET["dup"])) $error   = "Email or Roll No already exists.";
if (isset($_GET["fail"])) $error  = "Something went wrong.";

/* ---------- ACTIONS ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  // ADD STUDENT
  if ($action === "add") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    $roll_no   = trim($_POST["roll_no"] ?? "");
    $class_id  = (int)($_POST["class_id"] ?? 0);
    $password  = $_POST["password"] ?? "";

    if ($full_name === "" || $email === "" || $roll_no === "" || $class_id <= 0 || $password === "") {
      header("Location: students.php?req=1");
      exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      header("Location: students.php?req=1");
      exit;
    }
    if (strlen($password) < 8) {
      header("Location: students.php?req=1");
      exit;
    }

    $roll_no = strtoupper(preg_replace('/\s+/', '', $roll_no)); // normalize roll

    try {
      $pdo->beginTransaction();

      // insert into users
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users (role, full_name, email, phone, password_hash) VALUES ('student', ?, ?, ?, ?)");
      $stmt->execute([$full_name, $email, $phone, $hash]);
      $user_id = (int)$pdo->lastInsertId();

      // insert into students
      $stmt2 = $pdo->prepare("INSERT INTO students (user_id, roll_no, class_id) VALUES (?, ?, ?)");
      $stmt2->execute([$user_id, $roll_no, $class_id]);

      $pdo->commit();

      header("Location: students.php?ok=1");
      exit;

    } catch (PDOException $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      // duplicate email or roll
      header("Location: students.php?dup=1");
      exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      header("Location: students.php?fail=1");
      exit;
    }
  }

  // DELETE STUDENT (delete from users => students auto delete via FK cascade)
  if ($action === "delete") {
    $user_id = (int)($_POST["user_id"] ?? 0);
    if ($user_id > 0) {
      $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role='student'");
      $stmt->execute([$user_id]);
      header("Location: students.php?del=1");
      exit;
    }
    header("Location: students.php?fail=1");
    exit;
  }
}

/* ---------- FETCH DATA ---------- */
$classes = $pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetchAll();

$students = $pdo->query("
  SELECT u.user_id, u.full_name, u.email, u.phone, s.roll_no, c.class_name
  FROM students s
  JOIN users u ON u.user_id = s.user_id
  JOIN classes c ON c.class_id = s.class_id
  ORDER BY c.class_name, s.roll_no
")->fetchAll();

$total = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>EduInsight | Manage Students</title>
<style>
  :root{
    --bg:#070b1a; --text:#e9eeff; --muted:#a9b2d6;
    --accent:#7c5cff; --accent2:#00d4ff;
    --good:#2dff9a; --danger:#ff4d6d;
    --border:rgba(255,255,255,.12); --shadow:0 20px 70px rgba(0,0,0,.45);
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
  .wrap{width:100%; max-width:1100px; margin:18px auto; padding:0 16px;}
  .topbar{
    display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
    border:1px solid var(--border); border-radius:var(--radius);
    background:rgba(255,255,255,.04); box-shadow:var(--shadow); padding:16px;
  }
  .title{display:flex; gap:12px; align-items:center;}
  .logo{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--accent2));}
  h1{margin:0;font-size:18px;}
  .sub{margin:6px 0 0;color:var(--muted);font-weight:700;font-size:13px;}
  .btn{
    border:1px solid var(--border); background:rgba(255,255,255,.06); color:var(--text);
    padding:10px 12px; border-radius:12px; font-weight:900; cursor:pointer;
    display:inline-flex; align-items:center; justify-content:center; white-space:nowrap;
    transition:.18s ease;
  }
  .btn:hover{transform:translateY(-1px);background:rgba(255,255,255,.10);}
  .btn-primary{border:none;background:linear-gradient(135deg,var(--accent),var(--accent2));}
  .btn-danger{border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.10);color:#ffd1d9;}
  .grid{display:grid; grid-template-columns: 1fr 1.3fr; gap:14px; margin-top:14px; align-items:start;}
  .card{
    border:1px solid var(--border); border-radius:var(--radius);
    background:rgba(255,255,255,.04); box-shadow:0 18px 50px rgba(0,0,0,.22);
    padding:16px;
  }
  .kpi{display:flex; justify-content:space-between; align-items:center; gap:12px; padding-bottom:12px; border-bottom:1px solid var(--border);}
  .kpi .label{color:var(--muted); font-weight:900; font-size:12px;}
  .kpi .value{font-size:26px; font-weight:1000;}
  .msg{margin-top:14px;padding:10px 12px;border-radius:14px;font-weight:850;font-size:13px;line-height:1.4;}
  .msg.success{border:1px solid rgba(45,255,154,.25);background:rgba(45,255,154,.10);color:#d6ffe9;}
  .msg.error{border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.10);color:#ffd1d9;}
  label{display:block;font-weight:900;font-size:13px;margin:10px 0 6px;}
  .field{
    border:1px solid var(--border); background:rgba(255,255,255,.05);
    border-radius:14px; padding:10px 12px; transition:.18s ease;
  }
  .field:focus-within{border-color:rgba(0,212,255,.55); box-shadow:0 0 0 4px rgba(0,212,255,.10);}
  input, select{
    width:100%; border:none; outline:none; background:transparent;
    color:var(--text); font-size:14px; font-weight:800; padding:2px 0;
  }
  input::placeholder{color:rgba(169,178,214,.65); font-weight:650;}
  select option{background:#0b1020; color:var(--text);}
  .row{display:grid; grid-template-columns: 1fr 1fr; gap:12px;}
  table{width:100%; border-collapse:separate; border-spacing:0 10px; margin-top:12px;}
  th{color:var(--muted); font-size:12px; font-weight:900; text-align:left; padding:0 10px 8px;}
  td{
    padding:12px 10px; background:rgba(11,18,42,.35);
    border-top:1px solid var(--border); border-bottom:1px solid var(--border);
    font-weight:850;
  }
  tr td:first-child{border-left:1px solid var(--border); border-top-left-radius:14px; border-bottom-left-radius:14px;}
  tr td:last-child{border-right:1px solid var(--border); border-top-right-radius:14px; border-bottom-right-radius:14px;}
  @media (max-width: 950px){ .grid{grid-template-columns:1fr;} .row{grid-template-columns:1fr;} }
</style>

</head>
<body>

<div class="wrap">
  <div class="topbar">
    <div class="title">
      <div class="logo"></div>
      <div>
        <h1>Manage Students</h1>
        <div class="sub">Create student accounts & link them to class + roll no.</div>
      </div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="admin_dashboard.php">← Dashboard</a>
      <a class="btn btn-danger" href="../logout.php">Logout</a>
    </div>
  </div>

  <?php if($error): ?><div class="msg error" id="flashMsg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if($success): ?><div class="msg success" id="flashMsg"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="grid">
    <!-- ADD STUDENT -->
    <div class="card">
      <div class="kpi">
        <div>
          <div class="label">TOTAL STUDENTS</div>
          <div class="value"><?= (int)$total ?></div>
        </div>
        <div class="label">Step 3</div>
      </div>

      <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="add" />

        <label>Full Name</label>
        <div class="field"><input name="full_name" type="text" placeholder="e.g., Ali Khan" required></div>

        <div class="row">
          <div>
            <label>Email</label>
            <div class="field"><input name="email" type="email" placeholder="ali@student.com" required></div>
          </div>
          <div>
            <label>Phone</label>
            <div class="field"><input name="phone" type="text" placeholder="10-digit"></div>
          </div>
        </div>

        <div class="row">
          <div>
            <label>Roll No</label>
            <div class="field"><input name="roll_no" type="text" placeholder="TY101" required></div>
          </div>
          <div>
            <label>Class</label>
            <div class="field">
              <select name="class_id" required>
                <option value="">Choose class</option>
                <?php foreach($classes as $c): ?>
                  <option value="<?= (int)$c["class_id"] ?>"><?= htmlspecialchars($c["class_name"]) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <label>Password (min 8 chars)</label>
<div class="field" style="position:relative;">
  <input id="studentPassword" name="password" type="password" placeholder="Create password" required>
  <span id="togglePassword"
        style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
               cursor:pointer; font-size:12px; color:#00d4ff; font-weight:bold;">
        Show
  </span>
</div>
        <button class="btn btn-primary" type="submit" style="width:100%; margin-top:12px;">
          + Add Student
        </button>
      </form>
    </div>

    <!-- LIST -->
    <div class="card">
      <div class="kpi">
        <div>
          <div class="label">STUDENT LIST</div>
          <div class="value"><?= (int)$total ?></div>
        </div>
        <div class="label">Class-wise</div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Class</th>
            <th>Roll</th>
            <th>Name</th>
            <th>Email</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($students)): ?>
            <tr><td colspan="5">No students yet. Add first student from left.</td></tr>
          <?php else: ?>
            <?php foreach($students as $st): ?>
              <tr>
                <td><?= htmlspecialchars($st["class_name"]) ?></td>
                <td><?= htmlspecialchars($st["roll_no"]) ?></td>
                <td><?= htmlspecialchars($st["full_name"]) ?></td>
                <td><?= htmlspecialchars($st["email"]) ?></td>
                <td style="text-align:right;">
                  <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this student account?');">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="user_id" value="<?= (int)$st["user_id"] ?>" />
                    <button class="btn btn-danger" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const flash = document.getElementById("flashMsg");
  if (flash) setTimeout(() => flash.style.display = "none", 2500);
</script>
<script>
const toggle = document.getElementById("togglePassword");
const passwordField = document.getElementById("studentPassword");

toggle.addEventListener("click", function() {
    const type = passwordField.getAttribute("type");

    if (type === "password") {
        passwordField.setAttribute("type", "text");
        toggle.textContent = "Hide";
    } else {
        passwordField.setAttribute("type", "password");
        toggle.textContent = "Show";
    }
});
</script>

</body>
</html>