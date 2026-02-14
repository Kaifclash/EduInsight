<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "teacher") {
  header("Location: ../login.html");
  exit;
}

$name = $_SESSION["full_name"] ?? "Teacher";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>EduInsight | Teacher Dashboard</title>
<style>
  :root{--bg:#070b1a;--text:#e9eeff;--muted:#a9b2d6;--accent:#7c5cff;--accent2:#00d4ff;--border:rgba(255,255,255,.12);--radius:18px;}
  *{box-sizing:border-box;}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;color:var(--text);
    background:radial-gradient(900px 500px at 20% 10%, rgba(124,92,255,.25), transparent 55%),
             radial-gradient(900px 500px at 80% 20%, rgba(0,212,255,.20), transparent 55%),
             linear-gradient(180deg,#060816,var(--bg));
    min-height:100vh;}
  .wrap{max-width:1000px;margin:18px auto;padding:0 16px;}
  .top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;
    border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .card{margin-top:14px;border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .btn{display:inline-flex;gap:10px;align-items:center;justify-content:center;text-decoration:none;
    padding:12px 14px;border-radius:14px;font-weight:900;color:var(--text);
    border:1px solid var(--border);background:rgba(255,255,255,.06);}
  .btn-primary{border:none;background:linear-gradient(135deg,var(--accent),var(--accent2));}
  .muted{color:var(--muted);font-weight:700;font-size:13px;margin-top:6px;}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;}
  @media(max-width:800px){.grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <b style="font-size:18px;">Teacher Dashboard</b>
      <div class="muted">Welcome, <?= htmlspecialchars($name) ?> 👋</div>
    </div>
    <a class="btn" href="../logout.php">Logout</a>
  </div>

  <div class="grid">
    <div class="card">
      <b>Marks</b>
      <div class="muted">Enter student marks class-wise and exam-wise.</div>
      <div style="margin-top:12px;">
        <a class="btn btn-primary" href="marks_entry.php">Open Marks Entry</a>
      </div>
    </div>

    <div class="card">
      <b>My Profile</b>
      <div class="muted">View your account details.</div>
      <div style="margin-top:12px;">
        <a class="btn" href="#">Coming Soon</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>