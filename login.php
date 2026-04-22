<?php
require_once "helpers.php";
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-3">Login to ShowCase</h3>
                    <?php if ($error = get_flash("error")): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form action="actions/login_action.php" method="POST">
                        <input type="hidden" name="next" value="<?= e($_GET['next'] ?? '') ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" required class="form-control">
                        </div>
                        <button class="btn btn-success w-100">Login</button>
                    </form>
                    <p class="text-center mt-3 mb-0">
                        Don't have an account?
                        <a href="register.php" class="text-decoration-none fw-semibold">Create an account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>