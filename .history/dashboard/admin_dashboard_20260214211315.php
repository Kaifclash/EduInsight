<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: ../login.html");
  exit;
}
$fullName = $_SESSION["full_name"] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduInsight | Admin Dashboard</title>
  <style>
    :root{
      --bg:#070b1a;
      --card:#0f1736;
      --card2:#0b122a;
      --text:#e9eeff;
      --muted:#a9b2d6;
      --accent:#7c5cff;
      --accent2:#00d4ff;
      --good:#2dff9a;
      --warn:#ffcf5a;
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
        radial-gradient(900px 600px at 50% 110%, rgba(45,255,154,.12), transparent 60%),
        linear-gradient(180deg, #060816 0%, var(--bg) 55%, #040514 100%);
      min-height:100vh;
      overflow-x:hidden;
      overflow-y:auto; /* ✅ important */
    }

    a{color:inherit;text-decoration:none;}

    /* ✅ Full width responsive layout */
    .layout{
      width:100%;
      max-width:1400px;
      margin: 16px auto;
      padding: 0 16px;
      display:grid;
      grid-template-columns: 260px 1fr;
      gap:16px;
      align-items:start;
    }

    /* Sidebar */
    .sidebar{
      border:1px solid var(--border);
      border-radius: var(--radius);
      background: rgba(255,255,255,.04);
      box-shadow: var(--shadow);
      overflow:hidden;
      padding:16px;

      /* ✅ remove fixed height / sticky */
      position: relative;
      min-height: calc(100vh - 32px);
    }

    .brand{
      display:flex; align-items:center; gap:10px;
      font-weight:900;
      letter-spacing:.3px;
      margin-bottom:14px;
    }
    .logo{
      width:40px; height:40px;
      border-radius:12px;
      background:
        radial-gradient(circle at 30% 30%, rgba(255,255,255,.35), transparent 45%),
        linear-gradient(135deg, var(--accent), var(--accent2));
      box-shadow: 0 10px 30px rgba(124,92,255,.25);
    }
    .brand small{
      display:block;
      font-weight:650;
      color:var(--muted);
      margin-top:2px;
      font-size:12px;
      letter-spacing:0;
    }

    .nav{
      margin-top:10px;
      display:grid;
      gap:8px;
    }
    .nav a{
      padding:12px 12px;
      border:1px solid var(--border);
      border-radius: 14px;
      background: rgba(255,255,255,.03);
      color: var(--muted);
      font-weight:800;
      font-size:13px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      transition:.18s ease;
    }
    .nav a:hover{
      transform: translateY(-1px);
      background: rgba(255,255,255,.06);
      color: var(--text);
    }
    .pill{
      font-size:11px;
      font-weight:900;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid var(--border);
      background: rgba(255,255,255,.05);
      color: var(--muted);
    }

    .side-footer{
      margin-top:14px;
      border-top:1px solid var(--border);
      padding-top:14px;
      color: var(--muted);
      font-size:12px;
      font-weight:650;
      line-height:1.45;
    }

    /* Main */
    .main{
      border:1px solid var(--border);
      border-radius: var(--radius);
      background: rgba(255,255,255,.04);
      box-shadow: var(--shadow);
      overflow:hidden;
      position:relative;

      /* ✅ always fills screen */
      min-height: calc(100vh - 32px);
    }

    .main::before{
      content:"";
      position:absolute;
      inset:-60px -60px auto auto;
      width:260px; height:260px;
      background: radial-gradient(circle at 30% 30%, rgba(0,212,255,.18), transparent 60%);
      pointer-events:none;
    }

    /* Topbar */
    .topbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      padding:16px 18px;
      border-bottom:1px solid var(--border);
      backdrop-filter: blur(10px);
      background: rgba(7,11,26,.30);
      position:sticky;
      top:0;
      z-index:5;
    }

    .hello b{font-size:18px;}
    .hello div{color:var(--muted); font-weight:700; font-size:13px; margin-top:4px;}

    .actions{
      display:flex;
      gap:10px;
      align-items:center;
      flex-wrap:wrap;
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
      color: #ffd1d9;
    }

    /* Content */
    .content{padding:18px;}

    .grid{
      display:grid;
      grid-template-columns: repeat(4, 1fr);
      gap:12px;
    }

    .card{
      border:1px solid var(--border);
      border-radius: var(--radius);
      background: rgba(255,255,255,.03);
      padding:14px;
      box-shadow: 0 18px 50px rgba(0,0,0,.22);
    }
    .kpi .label{color:var(--muted); font-weight:900; font-size:12px;}
    .kpi .value{font-size:24px; font-weight:1000; margin-top:6px;}
    .kpi .sub{margin-top:8px; color:var(--muted); font-weight:700; font-size:12px;}

    .section-title{
      display:flex;
      align-items:flex-end;
      justify-content:space-between;
      gap:12px;
      margin:18px 0 10px;
    }
    .section-title h2{margin:0; font-size:16px;}
    .section-title p{margin:0; color:var(--muted); font-weight:700; font-size:12px;}

    .two{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:12px;
    }

    .list a{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:12px;
      border:1px solid var(--border);
      border-radius: 14px;
      background: rgba(11,18,42,.30);
      margin-top:8px;
      color: var(--muted);
      font-weight:850;
      font-size:13px;
      transition:.18s ease;
    }
    .list a:hover{transform: translateY(-1px); background: rgba(255,255,255,.06); color: var(--text);}
    .tag{
      font-size:11px;
      font-weight:900;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid var(--border);
      background: rgba(255,255,255,.05);
      color: var(--muted);
    }

    .note{
      margin-top:12px;
      padding:12px;
      border-radius: 16px;
      border:1px solid rgba(45,255,154,.25);
      background: rgba(45,255,154,.08);
      color:#d6ffe9;
      font-weight:800;
      font-size:13px;
      line-height:1.5;
    }

    /* ✅ Responsive */
    @media (max-width: 1050px){
      .layout{grid-template-columns: 1fr;}
      .sidebar{min-height:auto;}
      .grid{grid-template-columns: repeat(2, 1fr);}
      .two{grid-template-columns:1fr;}
    }
    @media (max-width: 520px){
      .grid{grid-template-columns:1fr;}
      .actions{width:100%;}
      .btn{flex:1;}
    }
  </style>
</head>
<body>

  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="brand">
        <div class="logo"></div>
        <div>
          EduInsight
          <small>Admin Panel</small>
        </div>
      </div>

      <div class="nav">
        <a href="admin_dashboard.php">Dashboard <span class="pill">Home</span></a>
        <a href="classes.php">Manage Classes</a>
        <a href="subjects.php">Manage Subjects</a>
        <a href="students.php">Manage Students <span class="pill">Open</span></a>        <a href="#" onclick="return comingSoon('Teachers');">Manage Teachers <span class="pill">Optional</span></a>       <a href="exams.php">Exams</a>
<a href="marks_entry.php">Marks Entry</a>

        <a href="#" onclick="return comingSoon('Analytics');">Analytics <span class="pill">Soon</span></a>
        <a href="#" onclick="return comingSoon('Reports');">Reports <span class="pill">Soon</span></a>
      </div>

      <div class="side-footer">
        Logged in as <b><?php echo htmlspecialchars($fullName); ?></b><br>
        Role: <b>Admin</b><br>
        <div style="margin-top:10px;">
          <a class="btn btn-danger" href="../logout.php" style="width:100%; text-align:center; display:inline-flex; justify-content:center;">Logout</a>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="main">
      <div class="topbar">
        <div class="hello">
          <b>Welcome, <?php echo htmlspecialchars($fullName); ?> 👋</b>
          <div>Here’s your institute overview (demo values for now).</div>
        </div>

        <div class="actions">
          <a class="btn" href="../index.html">View Site</a>
          <a class="btn btn-primary" href="#" onclick="return comingSoon('Quick Setup');">Quick Setup</a>
          <a class="btn btn-danger" href="../logout.php">Logout</a>
        </div>
      </div>

      <div class="content">
        <div class="grid">
          <div class="card kpi">
            <div class="label">Total Students</div>
            <div class="value" id="kStudents">0</div>
            <div class="sub">Add students to start tracking</div>
          </div>
          <div class="card kpi">
            <div class="label">Total Subjects</div>
            <div class="value" id="kSubjects">0</div>
            <div class="sub">Create subject list</div>
          </div>
          <div class="card kpi">
            <div class="label">Exams Created</div>
            <div class="value" id="kExams">0</div>
            <div class="sub">Unit, Mid, Final…</div>
          </div>
          <div class="card kpi">
            <div class="label">Average %</div>
            <div class="value" id="kAvg">—</div>
            <div class="sub">Will calculate from marks</div>
          </div>
        </div>

        <div class="note">
          ✅ Next we will build modules in this order:
          <b>Classes → Subjects → Students → Exams → Marks Entry → Analytics</b>
        </div>

        <div class="section-title">
          <h2>Quick Actions</h2>
          <p>Start setup in 2 minutes</p>
        </div>

        <div class="two">
          <div class="card">
            <h3 style="margin:0 0 6px;">Setup Checklist</h3>
            <div style="color:var(--muted); font-weight:700; font-size:13px; line-height:1.6;">
              1) Create Classes (FY/SY/TY)<br>
              2) Add Subjects (PHP/Java/DS/DBMS)<br>
              3) Add Students (Roll no, Class)<br>
              4) Create Exam (Mid/Final)<br>
              5) Enter Marks → Analytics auto
            </div>

            <div class="list">
              <a href="#" onclick="return comingSoon('Classes');">+ Add Classes <span class="tag">Step 1</span></a>
              <a href="#" onclick="return comingSoon('Subjects');">+ Add Subjects <span class="tag">Step 2</span></a>
              <a href="#" onclick="return comingSoon('Students');">+ Add Students <span class="tag">Step 3</span></a>
            </div>
          </div>

          <div class="card">
            <h3 style="margin:0 0 6px;">Recent Activity (Demo)</h3>
            <div style="color:var(--muted); font-weight:700; font-size:13px; line-height:1.6;">
              • No activity yet. Once you add data, this will show last actions.<br><br>
              Tip: After each module, we’ll update these KPIs from the database.
            </div>

            <div class="list">
              <a href="#" onclick="return comingSoon('Marks Entry');">Go to Marks Entry <span class="tag">Soon</span></a>
              <a href="#" onclick="return comingSoon('Analytics');">Open Analytics <span class="tag">Soon</span></a>
              <a href="#" onclick="return comingSoon('Reports');">Download Reports <span class="tag">Soon</span></a>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    function comingSoon(name){
      alert(name + " module next step me banayenge 👍");
      return false;
    }
  </script>

</body>
</html>
