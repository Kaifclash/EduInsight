<?php
session_start();
require __DIR__ . "/config/db.php";

function back_with_error($msg) {
    header("Location: login.html?error=" . urlencode($msg));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$login    = trim($_POST["email"] ?? "");   // email or roll
$password = $_POST["password"] ?? "";

if ($login === "" || $password === "") {
    back_with_error("Login and Password are required.");
}

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
    back_with_error("Invalid login or password.");
}

if (($user["status"] ?? "active") !== "active") {
    back_with_error("Your account is inactive. Contact admin.");
}

if (!password_verify($password, $user["password_hash"])) {
    back_with_error("Invalid login or password.");
}

// session
$_SESSION["user_id"]   = $user["user_id"];
$_SESSION["role"]      = $user["role"];
$_SESSION["full_name"] = $user["full_name"];

// redirect
if ($user["role"] === "admin") {
    header("Location: dashboard/admin_dashboard.php");
} elseif ($user["role"] === "teacher") {
    header("Location: dashboard/teacher_dashboard.php");
} else {
    header("Location: dashboard/student_dashboard.php");
}
exit;