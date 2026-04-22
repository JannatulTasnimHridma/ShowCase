<?php
require_once "helpers.php";
require_login();

$postId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$userId = (int) $_SESSION["user_id"];

if ($postId <= 0) {
    set_flash("error", "Invalid post id.");
    header("Location: dashboard.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT posts.*, events.event_date, events.event_time
    FROM posts
    LEFT JOIN events ON events.post_id = posts.id
    WHERE posts.id = ? AND posts.user_id = ?
");
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    set_flash("error", "Post not found or access denied.");
    header("Location: dashboard.php");
    exit;
}

$categoryResult = $conn->query("SELECT id, name, slug FROM categories WHERE slug IN ('skill','course','event','job') ORDER BY FIELD(slug,'skill','course','event','job')");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Edit Post</h4>
                    <form action="actions/update_post.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="post_id" value="<?= (int) $post["id"] ?>">
                        <input type="hidden" name="existing_image" value="<?= e((string) $post["image"]) ?>">
                        <input type="hidden" name="existing_video_file" value="<?= e((string) ($post["video_file"] ?? "")) ?>">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input name="title" required class="form-control" maxlength="180" value="<?= e($post["title"]) ?>">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                                        <option value="<?= (int) $cat["id"] ?>" <?= ((int) $cat["id"] === (int) $post["category_id"]) ? "selected" : "" ?>>
                                            <?= e($cat["name"]) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Post Type</label>
                                <select name="post_type" class="form-select" required>
                                    <?php $types = ["skill", "course", "event", "job"]; ?>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= e($type) ?>" <?= $post["post_type"] === $type ? "selected" : "" ?>>
                                            <?= e(ucfirst($type)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Price (optional)</label>
                            <input name="price" type="number" step="0.01" min="0" class="form-control" value="<?= e((string) $post["price"]) ?>">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Event Date (for event posts)</label>
                                <input name="event_date" type="date" class="form-control" value="<?= e((string) $post["event_date"]) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Event Time (for event posts)</label>
                                <input name="event_time" type="time" class="form-control" value="<?= e((string) $post["event_time"]) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Job Link (required for job posts)</label>
                            <input name="job_link" type="url" class="form-control" value="<?= e((string) ($post["job_link"] ?? "")) ?>" placeholder="https://example.com/job-posting">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="5" class="form-control" required><?= e($post["description"]) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Replace image (optional)</label>
                            <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Replace course video (optional, max 100MB)</label>
                            <input type="file" class="form-control" name="course_video" accept=".mp4,.webm,.mov,.mkv">
                            <?php if (!empty($post["video_file"])): ?>
                                <small class="text-muted">Current video: <?= e($post["video_file"]) ?></small>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary">Save Changes</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>
