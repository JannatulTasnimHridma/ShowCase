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
$name = trim($_POST["name"] ?? "");
$username = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$bio = trim($_POST["bio"] ?? "");

if ($name === "" || $username === "" || $email === "") {
    set_flash("error", "Name, username, and email are required.");
    header("Location: ../profile.php");
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    set_flash("error", "Username must be 3-30 chars and contain only letters, numbers, and underscore.");
    header("Location: ../profile.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash("error", "Please enter a valid email address.");
    header("Location: ../profile.php");
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
$checkStmt->bind_param("ssi", $email, $username, $userId);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    set_flash("error", "Email or username already used by another account.");
    header("Location: ../profile.php");
    exit;
}

$existingStmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$existingStmt->bind_param("i", $userId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
$profilePicture = $existing["profile_picture"] ?? null;

if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {
        set_flash("error", "Profile image upload failed.");
        header("Location: ../profile.php");
        exit;
    }

    if ($_FILES["profile_picture"]["size"] > 2 * 1024 * 1024) {
        set_flash("error", "Profile image is too large. Max 2MB.");
        header("Location: ../profile.php");
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES["profile_picture"]["tmp_name"]);
    finfo_close($finfo);

    $allowedMime = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowedMime[$mime])) {
        set_flash("error", "Only JPG, PNG, and WEBP files are allowed.");
        header("Location: ../profile.php");
        exit;
    }

    $newName = uniqid("profile_", true) . "." . $allowedMime[$mime];
    if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], "../uploads/" . $newName)) {
        set_flash("error", "Could not save profile image.");
        header("Location: ../profile.php");
        exit;
    }

    if (!empty($profilePicture) && file_exists("../uploads/" . $profilePicture)) {
        unlink("../uploads/" . $profilePicture);
    }

    $profilePicture = $newName;
}

$updateStmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, bio = ?, profile_picture = ? WHERE id = ?");
$updateStmt->bind_param("sssssi", $name, $username, $email, $bio, $profilePicture, $userId);

if ($updateStmt->execute()) {
    set_flash("success", "Profile updated successfully.");
    header("Location: ../profile.php");
    exit;
}

set_flash("error", "Failed to update profile.");
header("Location: ../profile.php");
exit;
