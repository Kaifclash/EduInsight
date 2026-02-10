<?php
session_start();
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

$id = $_GET["id"] ?? 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
    $stmt->execute([$id]);
}

header("Location: classes.php");
exit;
