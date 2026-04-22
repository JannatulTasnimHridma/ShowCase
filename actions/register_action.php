<?php
require_once "../helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../register.php");
    exit;
}

$name = trim($_POST["name"] ?? "");
$username = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($name === "" || $username === "" || $email === "" || $password === "") {
    set_flash("error", "All fields are required.");
    header("Location: ../register.php");
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    set_flash("error", "Username must be 3-30 chars and contain only letters, numbers, and underscore.");
    header("Location: ../register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash("error", "Please enter a valid email address.");
    header("Location: ../register.php");
    exit;
}

if (strlen($password) < 6) {
    set_flash("error", "Password must be at least 6 characters.");
    header("Location: ../register.php");
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$checkStmt->bind_param("ss", $email, $username);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    set_flash("error", "Email or username already registered.");
    header("Location: ../register.php");
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $username, $email, $passwordHash);

if ($stmt->execute()) {
    set_flash("success", "Registration successful. Please login.");
    header("Location: ../login.php");
    exit;
}

set_flash("error", "Registration failed. Please try again.");
header("Location: ../register.php");
exit;
?>