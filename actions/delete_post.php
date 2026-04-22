<?php
require_once "../helpers.php";

if (!is_logged_in()) {
    set_flash("error", "Please login first.");
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../dashboard.php");
    exit;
}

$postId = (int) ($_POST["post_id"] ?? 0);
$userId = (int) $_SESSION["user_id"];

if ($postId <= 0) {
    set_flash("error", "Invalid post id.");
    header("Location: ../dashboard.php");
    exit;
}

$stmt = $conn->prepare("SELECT image, video_file FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    set_flash("error", "Post not found or access denied.");
    header("Location: ../dashboard.php");
    exit;
}

$deleteStmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
$deleteStmt->bind_param("ii", $postId, $userId);

if ($deleteStmt->execute()) {
    if (!empty($post["image"]) && file_exists("../uploads/" . $post["image"])) {
        unlink("../uploads/" . $post["image"]);
    }
    if (!empty($post["video_file"]) && file_exists("../uploads/" . $post["video_file"])) {
        unlink("../uploads/" . $post["video_file"]);
    }
    set_flash("success", "Post deleted successfully.");
    header("Location: ../dashboard.php");
    exit;
}

set_flash("error", "Failed to delete post.");
header("Location: ../dashboard.php");
exit;
