<?php
require_once "helpers.php";

$username = trim($_GET["u"] ?? "");
if ($username === "") {
    set_flash("error", "User not found.");
    header("Location: index.php");
    exit;
}

$userStmt = $conn->prepare("
    SELECT id, name, username, email, bio, profile_picture, created_at
    FROM users
    WHERE username = ?
");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$profileUser = $userStmt->get_result()->fetch_assoc();

if (!$profileUser) {
    set_flash("error", "User not found.");
    header("Location: index.php");
    exit;
}

$postsStmt = $conn->prepare("
    SELECT posts.id, posts.title, posts.description, posts.image, posts.post_type, posts.created_at, categories.name AS category_name
    FROM posts
    JOIN categories ON categories.id = posts.category_id
    WHERE posts.user_id = ?
    ORDER BY posts.created_at DESC
");
$postsStmt->bind_param("i", $profileUser["id"]);
$postsStmt->execute();
$userPosts = $postsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($profileUser["name"]) ?> | ShowCase Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-banner { background: linear-gradient(120deg, #111827, #4f46e5); color: #fff; }
        .mini-card { transition: transform .2s ease, box-shadow .2s ease; }
        .mini-card:hover { transform: translateY(-4px); box-shadow: 0 .8rem 1.2rem rgba(0,0,0,.1); }
    </style>
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<header class="user-banner py-5 mb-4">
    <div class="container">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($profileUser["profile_picture"]) && file_exists(__DIR__ . "/uploads/" . $profileUser["profile_picture"])): ?>
                <img src="uploads/<?= e($profileUser["profile_picture"]) ?>" alt="Profile" class="rounded-circle border border-light" style="width:86px;height:86px;object-fit:cover;">
            <?php else: ?>
                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold" style="width:86px;height:86px;">
                    <?= e(strtoupper(substr($profileUser["name"], 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div>
                <h2 class="mb-1"><?= e($profileUser["name"]) ?></h2>
                <p class="mb-1">@<?= e($profileUser["username"]) ?></p>
                <small>Joined <?= e(date("d M Y", strtotime($profileUser["created_at"]))) ?></small>
            </div>
        </div>
    </div>
</header>
<main class="container pb-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5>About</h5>
                    <p class="text-muted mb-2"><?= !empty($profileUser["bio"]) ? nl2br(e($profileUser["bio"])) : "No bio added yet." ?></p>
                    <p class="mb-0"><strong>Posts:</strong> <?= (int) $userPosts->num_rows ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Posts by <?= e($profileUser["name"]) ?></h5>
                    <?php if ($userPosts->num_rows > 0): ?>
                        <div class="row g-3">
                            <?php while ($post = $userPosts->fetch_assoc()): ?>
                                <div class="col-md-6">
                                    <div class="card mini-card h-100 border-0 shadow-sm">
                                        <?php if (!empty($post["image"]) && file_exists(__DIR__ . "/uploads/" . $post["image"])): ?>
                                            <img src="uploads/<?= e($post["image"]) ?>" class="card-img-top" style="height:150px;object-fit:cover;" alt="<?= e($post["title"]) ?>">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="badge text-bg-primary"><?= e($post["category_name"]) ?></span>
                                                <span class="badge text-bg-dark"><?= e(ucfirst($post["post_type"])) ?></span>
                                            </div>
                                            <h6><?= e($post["title"]) ?></h6>
                                            <p class="small text-muted"><?= e(mb_strimwidth($post["description"], 0, 90, "...")) ?></p>
                                            <a class="btn btn-sm btn-outline-primary" href="post.php?id=<?= (int) $post["id"] ?>">View Post</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">This user has not posted yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>
