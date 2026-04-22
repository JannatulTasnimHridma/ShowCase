<?php
require_once __DIR__ . "/db.php";

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function is_logged_in(): bool
{
    return isset($_SESSION["user_id"]);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION["error"] = "Please login to continue.";
        header("Location: login.php");
        exit;
    }
}

function set_flash(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $message;
}

