<?php
session_start();
require __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$login    = trim($_POST["email"] ?? "");   // field name same rakha (email input), but ab email/roll dono accept
$password = $_POST["password"] ?? "";

if ($login === "" || $password === "") {
    die("Login and Password are required.");
}

/*
  If user enters email → match users.email
  If user enters roll → match students.roll_no (join users)
*/
$stmt = $pdo->prepare("
    SELECT u.user_id, u.role, u.full_name, u.password_hash, u.status
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    WHERE u.email = ? OR s.roll_no = ?
    LIMIT 1
");
$stmt->execute([$login, strtoupper(preg_replace('/\s+/', '', $login))]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid login or password.");
}

if (($user["status"] ?? "active") !== "active") {
    die("Your account is inactive. Contact admin.");
}

if (!password_verify($password, $user["password_hash"])) {
    die("Invalid login or password.");
}

// ✅ session
$_SESSION["user_id"]   = $user["user_id"];
$_SESSION["role"]      = $user["role"];
$_SESSION["full_name"] = $user["full_name"];

// ✅ redirect by role
if ($user["role"] === "admin") {
    header("Location: dashboard/admin_dashboard.php");
} elseif ($user["role"] === "teacher") {
    header("Location: dashboard/teacher_dashboard.php");
} else {
    header("Location: dashboard/student_dashboard.php");
}
exit;