<?php
require_once "helpers.php";

$postId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
if ($postId <= 0) {
    set_flash("error", "Invalid post selected.");
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT posts.*, users.name AS author_name, users.username AS author_username, categories.name AS category_name, events.event_date, events.event_time
    FROM posts
    JOIN users ON users.id = posts.user_id
    JOIN categories ON categories.id = posts.category_id
    LEFT JOIN events ON events.post_id = posts.id
    WHERE posts.id = ?
");
$stmt->bind_param("i", $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    set_flash("error", "Post not found.");
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($post["title"]) ?> | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <?php if (!empty($post["image"]) && file_exists(__DIR__ . "/uploads/" . $post["image"])): ?>
                    <img src="uploads/<?= e($post["image"]) ?>" class="card-img-top" style="max-height:420px;object-fit:cover;" alt="<?= e($post["title"]) ?>">
                <?php endif; ?>
                <div class="card-body p-4">
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge text-bg-primary"><?= e($post["category_name"]) ?></span>
                        <span class="badge text-bg-dark"><?= e(ucfirst($post["post_type"])) ?></span>
                    </div>
                    <h2 class="mb-3"><?= e($post["title"]) ?></h2>
                    <p class="text-muted">
                        By <a class="text-decoration-none" href="user.php?u=<?= urlencode((string) $post["author_username"]) ?>"><?= e($post["author_name"]) ?></a>
                        · <?= e(date("d M Y H:i", strtotime($post["created_at"]))) ?>
                    </p>
                    <p class="mt-3"><?= nl2br(e($post["description"])) ?></p>
                    <?php if ($post["post_type"] === "event" && !empty($post["event_date"]) && !empty($post["event_time"])): ?>
                        <p class="mb-1"><strong>Event Date:</strong> <?= e(date("d M Y", strtotime($post["event_date"]))) ?></p>
                        <p class="mb-3"><strong>Event Time:</strong> <?= e(date("h:i A", strtotime($post["event_time"]))) ?></p>
                    <?php endif; ?>
                    <?php if ($post["post_type"] === "job" && !empty($post["job_link"])): ?>
                        <p class="mb-3">
                            <?php if (is_logged_in()): ?>
                                <a href="<?= e($post["job_link"]) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Apply For This Job</a>
                            <?php else: ?>
                                <a href="login.php?next=<?= urlencode((string) $post["job_link"]) ?>" class="btn btn-primary">Apply For This Job</a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($post["post_type"] === "course" && !empty($post["video_file"]) && file_exists(__DIR__ . "/uploads/" . $post["video_file"])): ?>
                        <div class="mb-3">
                            <h6>Course Preview Video</h6>
                            <video controls style="width:100%;max-height:420px;" preload="metadata">
                                <source src="uploads/<?= e($post["video_file"]) ?>">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    <?php endif; ?>
                    <?php if ($post["price"] !== null): ?>
                        <p class="fw-semibold">Price: $<?= e(number_format((float) $post["price"], 2)) ?></p>
                    <?php endif; ?>
                    <?php if ($post["post_type"] !== "job"): ?>
                        <?php if (is_logged_in()): ?>
                            <a href="payment.php?post_id=<?= (int) $post["id"] ?>" class="btn btn-success">Buy Now</a>
                        <?php else: ?>
                            <a href="login.php?next=<?= urlencode('payment.php?post_id=' . (int) $post['id']) ?>" class="btn btn-success">Buy Now</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>
