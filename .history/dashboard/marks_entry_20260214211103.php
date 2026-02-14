<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: ../login.html");
  exit;
}

$success = "";
$error = "";
if (isset($_GET["ok"]))  $success = "Marks saved successfully.";
if (isset($_GET["upd"])) $success = "Marks updated successfully.";
if (isset($_GET["req"])) $error   = "All fields are required.";
if (isset($_GET["bad"])) $error   = "Marks invalid.";

/* Fetch dropdown data */
$exams = $pdo->query("SELECT exam_id, exam_name FROM exams ORDER BY created_at DESC")->fetchAll();
$classes = $pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC")->fetchAll();

/* optional filter */
$selectedClass = (int)($_GET["class_id"] ?? 0);

/* Students list (filtered by class if chosen) */
if ($selectedClass > 0) {
  $st = $pdo->prepare("
    SELECT s.student_id, s.roll_no, u.full_name
    FROM students s JOIN users u ON u.user_id=s.user_id
    WHERE s.class_id=?
    ORDER BY s.roll_no
  ");
  $st->execute([$selectedClass]);
  $students = $st->fetchAll();

  $sub = $pdo->prepare("SELECT subject_id, subject_name FROM subjects WHERE class_id=? ORDER BY subject_name");
  $sub->execute([$selectedClass]);
  $subjects = $sub->fetchAll();
} else {
  $students = [];
  $subjects = [];
}

/* Save marks (UPSERT) */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $exam_id    = (int)($_POST["exam_id"] ?? 0);
  $student_id = (int)($_POST["student_id"] ?? 0);
  $subject_id = (int)($_POST["subject_id"] ?? 0);
  $marks      = (float)($_POST["marks_obtained"] ?? 0);
  $max        = (float)($_POST["max_marks"] ?? 100);

  if ($exam_id<=0 || $student_id<=0 || $subject_id<=0) { header("Location: marks_entry.php?req=1&class_id=".$selectedClass); exit; }
  if ($max<=0 || $marks<0 || $marks>$max) { header("Location: marks_entry.php?bad=1&class_id=".$selectedClass); exit; }

  // check exists
  $chk = $pdo->prepare("SELECT mark_id FROM marks WHERE exam_id=? AND student_id=? AND subject_id=?");
  $chk->execute([$exam_id,$student_id,$subject_id]);
  $exists = $chk->fetchColumn();

  if ($exists) {
    $up = $pdo->prepare("UPDATE marks SET marks_obtained=?, max_marks=? WHERE mark_id=?");
    $up->execute([$marks,$max,$exists]);
    header("Location: marks_entry.php?upd=1&class_id=".$selectedClass); exit;
  } else {
    $ins = $pdo->prepare("INSERT INTO marks (exam_id, student_id, subject_id, marks_obtained, max_marks) VALUES (?,?,?,?,?)");
    $ins->execute([$exam_id,$student_id,$subject_id,$marks,$max]);
    header("Location: marks_entry.php?ok=1&class_id=".$selectedClass); exit;
  }
}

/* Recent entries (optional view) */
$recent = $pdo->query("
  SELECT m.marks_obtained, m.max_marks, e.exam_name, u.full_name, s.roll_no, c.class_name, sb.subject_name
  FROM marks m
  JOIN exams e ON e.exam_id=m.exam_id
  JOIN students s ON s.student_id=m.student_id
  JOIN users u ON u.user_id=s.user_id
  JOIN classes c ON c.class_id=s.class_id
  JOIN subjects sb ON sb.subject_id=m.subject_id
  ORDER BY m.created_at DESC
  LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>EduInsight | Marks Entry</title>
<style>
  :root{--bg:#070b1a;--text:#e9eeff;--muted:#a9b2d6;--accent:#7c5cff;--accent2:#00d4ff;--danger:#ff4d6d;--good:#2dff9a;--border:rgba(255,255,255,.12);--radius:18px;}
  *{box-sizing:border-box;}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;color:var(--text);
    background:radial-gradient(900px 500px at 20% 10%, rgba(124,92,255,.25), transparent 55%),
             radial-gradient(900px 500px at 80% 20%, rgba(0,212,255,.20), transparent 55%),
             linear-gradient(180deg,#060816,var(--bg));
    min-height:100vh; padding:18px;}
  .wrap{max-width:1100px;margin:0 auto;}
  .top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;
    border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .btn{border:1px solid var(--border);background:rgba(255,255,255,.06);color:var(--text);padding:10px 12px;border-radius:12px;font-weight:900;cursor:pointer;text-decoration:none;}
  .btn-primary{border:none;background:linear-gradient(135deg,var(--accent),var(--accent2));}
  .card{margin-top:14px;border:1px solid var(--border);border-radius:var(--radius);background:rgba(255,255,255,.04);padding:16px;}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  label{display:block;font-weight:900;font-size:13px;margin:10px 0 6px;}
  .field{border:1px solid var(--border);background:rgba(255,255,255,.05);border-radius:14px;padding:10px 12px;}
  input,select{width:100%;border:none;outline:none;background:transparent;color:var(--text);font-weight:800;}
  select option{background:#0b1020;color:var(--text);}
  .msg{margin-top:12px;padding:10px 12px;border-radius:14px;font-weight:850;font-size:13px;}
  .ok{border:1px solid rgba(45,255,154,.25);background:rgba(45,255,154,.10);color:#d6ffe9;}
  .err{border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.10);color:#ffd1d9;}
  table{width:100%;margin-top:12px;border-collapse:separate;border-spacing:0 10px;}
  th{color:var(--muted);font-size:12px;font-weight:900;text-align:left;padding:0 10px 8px;}
  td{padding:12px 10px;background:rgba(11,18,42,.35);border-top:1px solid var(--border);border-bottom:1px solid var(--border);font-weight:850;}
  tr td:first-child{border-left:1px solid var(--border);border-top-left-radius:14px;border-bottom-left-radius:14px;}
  tr td:last-child{border-right:1px solid var(--border);border-top-right-radius:14px;border-bottom-right-radius:14px;}
  @media(max-width:900px){.grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <b style="font-size:18px;">Marks Entry</b>
      <div style="color:var(--muted);font-weight:700;font-size:13px;margin-top:4px;">Select class → student → subject → exam</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="admin_dashboard.php">← Dashboard</a>
      <a class="btn" href="exams.php">Exams</a>
      <a class="btn" href="../logout.php">Logout</a>
    </div>
  </div>

  <?php if($success): ?><div class="msg ok" id="flashMsg"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="msg err" id="flashMsg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="GET" class="grid">
      <div>
        <label>Choose Class (to load students & subjects)</label>
        <div class="field">
          <select name="class_id" onchange="this.form.submit()">
            <option value="0">-- Select Class --</option>
            <?php foreach($classes as $c): ?>
              <option value="<?= (int)$c["class_id"] ?>" <?= $selectedClass===(int)$c["class_id"]?'selected':'' ?>>
                <?= htmlspecialchars($c["class_name"]) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div></div>
    </form>

    <form method="POST" class="grid" autocomplete="off">
      <div>
        <label>Exam</label>
        <div class="field">
          <select name="exam_id" required>
            <option value="">-- Select Exam --</option>
            <?php foreach($exams as $e): ?>
              <option value="<?= (int)$e["exam_id"] ?>"><?= htmlspecialchars($e["exam_name"]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label>Student</label>
        <div class="field">
          <select name="student_id" required <?= $selectedClass>0?'':'disabled' ?>>
            <option value=""><?= $selectedClass>0 ? '-- Select Student --' : 'Select class first' ?></option>
            <?php foreach($students as $s): ?>
              <option value="<?= (int)$s["student_id"] ?>"><?= htmlspecialchars($s["roll_no"]." - ".$s["full_name"]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label>Subject</label>
        <div class="field">
          <select name="subject_id" required <?= $selectedClass>0?'':'disabled' ?>>
            <option value=""><?= $selectedClass>0 ? '-- Select Subject --' : 'Select class first' ?></option>
            <?php foreach($subjects as $sb): ?>
              <option value="<?= (int)$sb["subject_id"] ?>"><?= htmlspecialchars($sb["subject_name"]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid" style="grid-template-columns:1fr 1fr; gap:12px;">
        <div>
          <label>Marks Obtained</label>
          <div class="field"><input type="number" step="0.01" name="marks_obtained" placeholder="e.g., 78" required></div>
        </div>
        <div>
          <label>Max Marks</label>
          <div class="field"><input type="number" step="0.01" name="max_marks" value="100" required></div>
        </div>
      </div>

      <input type="hidden" name="class_id" value="<?= (int)$selectedClass ?>" />

      <button class="btn btn-primary" type="submit" style="width:100%; margin-top:12px;">Save Marks</button>
    </form>
  </div>

  <div class="card">
    <b>Recent Entries</b>
    <table>
      <thead>
        <tr><th>Exam</th><th>Class</th><th>Student</th><th>Subject</th><th>Marks</th></tr>
      </thead>
      <tbody>
      <?php if(empty($recent)): ?>
        <tr><td colspan="5">No marks entered yet.</td></tr>
      <?php else: foreach($recent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r["exam_name"]) ?></td>
          <td><?= htmlspecialchars($r["class_name"]) ?></td>
          <td><?= htmlspecialchars($r["roll_no"]." - ".$r["full_name"]) ?></td>
          <td><?= htmlspecialchars($r["subject_name"]) ?></td>
          <td><?= htmlspecialchars($r["marks_obtained"])."/".htmlspecialchars($r["max_marks"]) ?></td>
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
