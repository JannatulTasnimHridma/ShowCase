<?php
require_once "helpers.php";
require_login();

$userId = (int) $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT id, name, username, email, bio, profile_picture, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-3">My Profile</h4>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if (!empty($user["profile_picture"]) && file_exists(__DIR__ . "/uploads/" . $user["profile_picture"])): ?>
                            <img src="uploads/<?= e($user["profile_picture"]) ?>" alt="Profile Picture" class="rounded-circle border" style="width:84px;height:84px;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary-subtle border d-flex align-items-center justify-content-center" style="width:84px;height:84px;">
                                <span class="fw-bold text-secondary"><?= e(strtoupper(substr($user["name"] ?? "U", 0, 1))) ?></span>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="mb-1 fw-semibold"><?= e($user["name"] ?? "") ?></p>
                            <p class="mb-1 text-muted">@<?= e($user["username"] ?? "") ?></p>
                            <small class="text-secondary">Member since <?= e(date("d M Y", strtotime($user["created_at"] ?? "now"))) ?></small>
                        </div>
                    </div>

                    <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required maxlength="100" value="<?= e($user["name"] ?? "") ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+" value="<?= e($user["username"] ?? "") ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required maxlength="190" value="<?= e($user["email"] ?? "") ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="4" maxlength="1000"><?= e($user["bio"] ?? "") ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Profile Picture (JPG/PNG/WEBP, max 2MB)</label>
                            <input type="file" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <button class="btn btn-primary">Save Profile</button>
                    </form>
                </div>
            </div>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Change Password</h5>
                    <form action="actions/change_password.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button class="btn btn-outline-dark">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>
