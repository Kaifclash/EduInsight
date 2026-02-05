<?php
session_start();
require __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Basic validation
if ($email === "" || $password === "") {
    die("Email and Password are required.");
}

// Fetch user by email
$stmt = $pdo->prepare("
    SELECT user_id, role, full_name, password_hash, status 
    FROM users 
    WHERE email = ? 
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid email or password.");
}

// Check account status
if ($user["status"] !== "active") {
    die("Your account is inactive. Contact admin.");
}

// Verify password
if (!password_verify($password, $user["password_hash"])) {
    die("Invalid email or password.");
}

// ✅ Login success → session set
$_SESSION["user_id"]   = $user["user_id"];
$_SESSION["role"]      = $user["role"];
$_SESSION["full_name"] = $user["full_name"];

// Redirect based on role
if ($user["role"] === "admin") {
    header("Location: dashboard/admin_dashboard.php");
} elseif ($user["role"] === "teacher") {
    header("Location: dashboard/teacher_dashboard.php");
} else {
    header("Location: dashboard/student_dashboard.php");
}
exit;