<?php
require_once "../helpers.php";

if (!is_logged_in()) {
    set_flash("error", "Please login first.");
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../profile.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$currentPassword = $_POST["current_password"] ?? "";
$newPassword = $_POST["new_password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
    set_flash("error", "All password fields are required.");
    header("Location: ../profile.php");
    exit;
}

if (strlen($newPassword) < 6) {
    set_flash("error", "New password must be at least 6 characters.");
    header("Location: ../profile.php");
    exit;
}

if ($newPassword !== $confirmPassword) {
    set_flash("error", "New password and confirm password do not match.");
    header("Location: ../profile.php");
    exit;
}

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($currentPassword, $user["password"])) {
    set_flash("error", "Current password is incorrect.");
    header("Location: ../profile.php");
    exit;
}

if (password_verify($newPassword, $user["password"])) {
    set_flash("error", "New password must be different from current password.");
    header("Location: ../profile.php");
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$updateStmt->bind_param("si", $newHash, $userId);

if ($updateStmt->execute()) {
    set_flash("success", "Password updated successfully.");
    header("Location: ../profile.php");
    exit;
}

set_flash("error", "Failed to change password.");
header("Location: ../profile.php");
exit;
