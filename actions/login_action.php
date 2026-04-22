<?php
require_once "../helpers.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$next = trim($_POST["next"] ?? "");

if ($email === "" || $password === "") {
    set_flash("error", "Email and password are required.");
    header("Location: ../login.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user["password"])) {
    $_SESSION["user_id"] = (int) $user["id"];
    set_flash("success", "Welcome back.");
    if ($next !== "") {
        // If next is an absolute URL, redirect there. Otherwise treat as local path relative to project root.
        if (preg_match('/^https?:\/\//i', $next)) {
            header("Location: " . $next);
            exit;
        }
        // Prevent leading slashes causing double relative paths
        $next = ltrim($next, '/');
        header("Location: ../" . $next);
        exit;
    }
    header("Location: ../dashboard.php");
    exit;
}

set_flash("error", "Invalid login credentials.");
header("Location: ../login.php");
exit;
?>