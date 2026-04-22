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

$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$categoryId = (int) ($_POST["category_id"] ?? 0);
$postType = $_POST["post_type"] ?? "skill";
$price = trim((string) ($_POST["price"] ?? ""));
$eventDate = trim($_POST["event_date"] ?? "");
$eventTime = trim($_POST["event_time"] ?? "");
$jobLink = trim($_POST["job_link"] ?? "");
$userId = (int) $_SESSION["user_id"];

$allowedTypes = ["skill", "course", "event", "job"];
if ($title === "" || $description === "" || $categoryId <= 0 || !in_array($postType, $allowedTypes, true)) {
    set_flash("error", "Please provide valid post details.");
    header("Location: ../dashboard.php");
    exit;
}

if ($postType === "event" && ($eventDate === "" || $eventTime === "")) {
    set_flash("error", "Event posts require both event date and time.");
    header("Location: ../dashboard.php");
    exit;
}

if ($postType === "job") {
    if ($jobLink === "" || !filter_var($jobLink, FILTER_VALIDATE_URL)) {
        set_flash("error", "Job posts require a valid job link.");
        header("Location: ../dashboard.php");
        exit;
    }
} else {
    $jobLink = "";
}

$imageName = null;
if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        set_flash("error", "Image upload failed.");
        header("Location: ../dashboard.php");
        exit;
    }

    if ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
        set_flash("error", "Image is too large. Max 2MB.");
        header("Location: ../dashboard.php");
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
        header("Location: ../dashboard.php");
        exit;
    }

    $imageName = uniqid("post_", true) . "." . $allowedMime[$mime];
    $target = "../uploads/" . $imageName;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
        set_flash("error", "Unable to save uploaded image.");
        header("Location: ../dashboard.php");
        exit;
    }
}

$videoFile = null;
if ($postType === "course" && isset($_FILES["course_video"]) && $_FILES["course_video"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["course_video"]["error"] !== UPLOAD_ERR_OK) {
        set_flash("error", "Video upload failed.");
        header("Location: ../dashboard.php");
        exit;
    }

    if ($_FILES["course_video"]["size"] > 100 * 1024 * 1024) {
        set_flash("error", "Course video is too large. Max 100MB.");
        header("Location: ../dashboard.php");
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
        header("Location: ../dashboard.php");
        exit;
    }

    $videoFile = uniqid("course_", true) . "." . $allowedVideoMime[$videoMime];
    if (!move_uploaded_file($_FILES["course_video"]["tmp_name"], "../uploads/" . $videoFile)) {
        set_flash("error", "Unable to save course video.");
        header("Location: ../dashboard.php");
        exit;
    }
}

$priceValue = null;
if ($price !== "") {
    if (!is_numeric($price) || (float) $price < 0) {
        set_flash("error", "Price must be a valid positive number.");
        header("Location: ../dashboard.php");
        exit;
    }
    $priceValue = (float) $price;
}

if ($priceValue === null) {
    $stmt = $conn->prepare("
        INSERT INTO posts (user_id, category_id, title, description, image, video_file, job_link, post_type, price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)
    ");
    $stmt->bind_param("iissssss", $userId, $categoryId, $title, $description, $imageName, $videoFile, $jobLink, $postType);
} else {
    $stmt = $conn->prepare("
        INSERT INTO posts (user_id, category_id, title, description, image, video_file, job_link, post_type, price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissssssd", $userId, $categoryId, $title, $description, $imageName, $videoFile, $jobLink, $postType, $priceValue);
}

if ($stmt->execute()) {
    $postId = $stmt->insert_id;
    if ($postType === "event") {
        $eventStmt = $conn->prepare("INSERT INTO events (post_id, event_date, event_time) VALUES (?, ?, ?)");
        $eventStmt->bind_param("iss", $postId, $eventDate, $eventTime);
        $eventStmt->execute();
    }
    set_flash("success", "Post created successfully.");
    header("Location: ../dashboard.php");
    exit;
}

set_flash("error", "Failed to create post.");
header("Location: ../dashboard.php");
exit;
?>