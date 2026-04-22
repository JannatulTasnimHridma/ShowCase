<?php
require_once "helpers.php";
require_login();

$userId = (int) $_SESSION["user_id"];

$userStmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$categoryResult = $conn->query("SELECT id, name, slug FROM categories WHERE slug IN ('skill','course','event','job') ORDER BY FIELD(slug,'skill','course','event','job')");

$postsStmt = $conn->prepare("
    SELECT posts.id, posts.title, posts.post_type, posts.created_at, categories.name AS category_name
    FROM posts
    JOIN categories ON categories.id = posts.category_id
    WHERE posts.user_id = ?
    ORDER BY posts.created_at DESC
");
$postsStmt->bind_param("i", $userId);
$postsStmt->execute();
$posts = $postsStmt->get_result();
$postCount = (int) ($conn->query("SELECT COUNT(*) AS c FROM posts WHERE user_id = " . $userId)->fetch_assoc()["c"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dash-hero {
            background: linear-gradient(120deg, #1f2937, #4338ca);
            color: #fff;
            border-radius: 18px;
        }
        .dash-card { border-radius: 14px; }
        .soft-input .form-control, .soft-input .form-select { border-radius: 10px; }
        .post-row:hover { background-color: #f8f9ff; }
    </style>
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<main class="container py-4">
    <?php if ($success = get_flash("success")): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error = get_flash("error")): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="dash-hero p-4 p-lg-5 mb-4 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <h2 class="fw-bold mb-1">Welcome back, <?= e($user["name"] ?? "Creator") ?></h2>
                <p class="mb-0" style="color: #e5e7eb;">Manage your profile, publish posts, and grow your audience from one place.</p>
            </div>
            <div class="col-lg-4">
                <div class="bg-white text-dark rounded-3 p-3">
                    <p class="mb-1 text-secondary small">Your total posts</p>
                    <h3 class="mb-0"><?= $postCount ?></h3>
                    <a href="profile.php" class="btn btn-outline-dark btn-sm mt-2">Edit Profile</a>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card dash-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h4 class="card-title mb-3">Profile Summary</h4>
                    <p class="mb-1"><strong>Name:</strong> <?= e($user["name"] ?? "") ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= e($user["email"] ?? "") ?></p>
                    <p class="text-muted mb-0"><small>Member since: <?= e(date("d M Y", strtotime($user["created_at"] ?? "now"))) ?></small></p>
                    <hr>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Go to My Profile</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card dash-card border-0 shadow-sm">
                <div class="card-body soft-input">
                    <h4 class="card-title mb-3">Create New Post</h4>
                    <form action="actions/add_post.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Title</label>
                                <input name="title" required class="form-control" maxlength="180">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                                        <option value="<?= (int) $cat["id"] ?>"><?= e($cat["name"]) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Post Type</label>
                                <select name="post_type" class="form-select" required>
                                    <option value="skill">Skill</option>
                                    <option value="course">Course</option>
                                    <option value="event">Event</option>
                                    <option value="job">Job</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price (optional)</label>
                                <input name="price" type="number" step="0.01" min="0" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Event Date (for event posts)</label>
                                <input name="event_date" type="date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Event Time (for event posts)</label>
                                <input name="event_time" type="time" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Job Link (required for job posts)</label>
                                <input name="job_link" type="url" class="form-control" placeholder="https://example.com/job-posting">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Image (jpg/png/webp, max 2MB)</label>
                                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Course Video (optional for course, max 100MB)</label>
                                <input type="file" name="course_video" class="form-control" accept=".mp4,.webm,.mov,.mkv">
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3 px-4">Publish Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card dash-card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">My Posts</h4>
                <span class="badge text-bg-secondary"><?= $postCount ?> total</span>
            </div>
            <?php if ($posts->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($post = $posts->fetch_assoc()): ?>
                            <tr class="post-row">
                                <td><?= e($post["title"]) ?></td>
                                <td><span class="badge text-bg-dark"><?= e(ucfirst($post["post_type"])) ?></span></td>
                                <td><?= e($post["category_name"]) ?></td>
                                <td><?= e(date("d M Y", strtotime($post["created_at"]))) ?></td>
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="post.php?id=<?= (int) $post["id"] ?>">View</a>
                                    <a class="btn btn-sm btn-outline-warning" href="edit_post.php?id=<?= (int) $post["id"] ?>">Edit</a>
                                    <form action="actions/delete_post.php" method="POST" onsubmit="return confirm('Delete this post?')">
                                        <input type="hidden" name="post_id" value="<?= (int) $post["id"] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">You have not created any posts yet.</div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>