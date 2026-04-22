<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "showcase1");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

// Lightweight self-healing for older local databases.
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(80) NULL UNIQUE AFTER name");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER bio");
$conn->query("ALTER TABLE posts ADD COLUMN IF NOT EXISTS video_file VARCHAR(255) NULL AFTER image");
$conn->query("ALTER TABLE posts ADD COLUMN IF NOT EXISTS job_link VARCHAR(500) NULL AFTER video_file");
$conn->query("ALTER TABLE posts MODIFY COLUMN post_type ENUM('course','skill','event','job','service','other') DEFAULT 'skill'");
$conn->query("INSERT IGNORE INTO categories (name, slug) VALUES ('Job', 'job')");
?>