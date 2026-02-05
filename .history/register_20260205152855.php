<?php
require __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit;
}

$full_name      = trim($_POST["full_name"] ?? "");
$institute_name = trim($_POST["institute_name"] ?? "");
$email          = trim($_POST["email"] ?? "");
$phone          = trim($_POST["phone"] ?? "");
$password       = $_POST["password"] ?? "";
$confirm        = $_POST["confirm_password"] ?? "";

// Basic validations
if ($full_name === "" || $institute_name === "" || $email === "" || $password === "" || $confirm === "") {
    die("All required fields must be filled.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}

$digits = preg_replace('/\D+/', '', $phone);
if ($phone !== "" && strlen($digits) !== 10) {
    die("Mobile number must be 10 digits.");
}

if (strlen($password) < 8) {
    die("Password must be at least 8 characters.");
}

if ($password !== $confirm) {
    die("Confirm password does not match.");
}

// Check if email already exists
$check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$check->execute([$email]);
if ($check->fetch()) {
    die("Email already registered. Please login.");
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert admin
$stmt = $pdo->prepare("
    INSERT INTO users (role, full_name, institute_name, email, phone, password_hash)
    VALUES ('admin', ?, ?, ?, ?, ?)
");
$stmt->execute([$full_name, $institute_name, $email, $phone, $password_hash]);

// Redirect to login
header("Location: login.html?registered=1");
exit;