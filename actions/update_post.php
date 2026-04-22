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
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$categoryId = (int) ($_POST["category_id"] ?? 0);
$postType = $_POST["post_type"] ?? "skill";
$price = trim((string) ($_POST["price"] ?? ""));
$eventDate = trim($_POST["event_date"] ?? "");
$eventTime = trim($_POST["event_time"] ?? "");
$jobLink = trim($_POST["job_link"] ?? "");
$existingImage = trim($_POST["existing_image"] ?? "");
$existingVideoFile = trim($_POST["existing_video_file"] ?? "");
$allowedTypes = ["skill", "course", "event", "job"];

if ($postId <= 0 || $title === "" || $description === "" || $categoryId <= 0 || !in_array($postType, $allowedTypes, true)) {
    set_flash("error", "Please provide valid details.");
    header("Location: ../dashboard.php");
    exit;
}

if ($postType === "event" && ($eventDate === "" || $eventTime === "")) {
    set_flash("error", "Event posts require both event date and time.");
    header("Location: ../edit_post.php?id=" . $postId);
    exit;
}

if ($postType === "job") {
    if ($jobLink === "" || !filter_var($jobLink, FILTER_VALIDATE_URL)) {
        set_flash("error", "Job posts require a valid job link.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }
} else {
    $jobLink = "";
}

$checkStmt = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $postId, $userId);
$checkStmt->execute();
if (!$checkStmt->get_result()->fetch_assoc()) {
    set_flash("error", "Post not found or access denied.");
    header("Location: ../dashboard.php");
    exit;
}

$newImage = $existingImage;
if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK || $_FILES["image"]["size"] > 2 * 1024 * 1024) {
        set_flash("error", "Invalid image upload.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES["image"]["tmp_name"]);
    finfo_close($finfo);
    $allowedMime = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowedMime[$mime])) {
        set_flash("error", "Only JPG, PNG, and WEBP images are allowed.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }

    $newImage = uniqid("post_", true) . "." . $allowedMime[$mime];
    $target = "../uploads/" . $newImage;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
        set_flash("error", "Unable to save uploaded image.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }

    if ($existingImage !== "" && file_exists("../uploads/" . $existingImage)) {
        unlink("../uploads/" . $existingImage);
    }
}

$newVideoFile = $existingVideoFile;
if (isset($_FILES["course_video"]) && $_FILES["course_video"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["course_video"]["error"] !== UPLOAD_ERR_OK) {
        set_flash("error", "Video upload failed.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }
    if ($_FILES["course_video"]["size"] > 100 * 1024 * 1024) {
        set_flash("error", "Course video is too large. Max 100MB.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $videoMime = finfo_file($finfo, $_FILES["course_video"]["tmp_name"]);
    finfo_close($finfo);
    $allowedVideoMime = [
        "video/mp4" => "mp4",
        "video/webm" => "webm",
        "video/quicktime" => "mov",
        "video/x-matroska" => "mkv"
    ];

    if (!isset($allowedVideoMime[$videoMime])) {
        set_flash("error", "Only MP4, WEBM, MOV, and MKV videos are allowed.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }

    $newVideoFile = uniqid("course_", true) . "." . $allowedVideoMime[$videoMime];
    $targetVideo = "../uploads/" . $newVideoFile;
    if (!move_uploaded_file($_FILES["course_video"]["tmp_name"], $targetVideo)) {
        set_flash("error", "Unable to save course video.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }
    if (!empty($existingVideoFile) && file_exists("../uploads/" . $existingVideoFile)) {
        unlink("../uploads/" . $existingVideoFile);
    }
}

if ($postType !== "course" && !empty($newVideoFile)) {
    if (file_exists("../uploads/" . $newVideoFile)) {
        unlink("../uploads/" . $newVideoFile);
    }
    $newVideoFile = null;
}

$priceValue = null;
if ($price !== "") {
    if (!is_numeric($price) || (float) $price < 0) {
        set_flash("error", "Price must be a valid positive number.");
        header("Location: ../edit_post.php?id=" . $postId);
        exit;
    }
    $priceValue = (float) $price;
}

if ($priceValue === null) {
    $stmt = $conn->prepare("
        UPDATE posts
        SET category_id = ?, title = ?, description = ?, image = ?, video_file = ?, job_link = ?, post_type = ?, price = NULL
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("issssssii", $categoryId, $title, $description, $newImage, $newVideoFile, $jobLink, $postType, $postId, $userId);
} else {
    $stmt = $conn->prepare("
        UPDATE posts
        SET category_id = ?, title = ?, description = ?, image = ?, video_file = ?, job_link = ?, post_type = ?, price = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("issssssdii", $categoryId, $title, $description, $newImage, $newVideoFile, $jobLink, $postType, $priceValue, $postId, $userId);
}

if ($stmt->execute()) {
    if ($postType === "event") {
        $eventStmt = $conn->prepare("
            INSERT INTO events (post_id, event_date, event_time)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE event_date = VALUES(event_date), event_time = VALUES(event_time)
        ");
        $eventStmt->bind_param("iss", $postId, $eventDate, $eventTime);
        $eventStmt->execute();
    } else {
        $deleteEventStmt = $conn->prepare("DELETE FROM events WHERE post_id = ?");
        $deleteEventStmt->bind_param("i", $postId);
        $deleteEventStmt->execute();
    }
    set_flash("success", "Post updated successfully.");
    header("Location: ../dashboard.php");
    exit;
}

set_flash("error", "Failed to update post.");
header("Location: ../edit_post.php?id=" . $postId);
exit;
