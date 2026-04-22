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
    <title>Register | ShowCase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-3">Create your account</h3>
                    <?php if ($error = get_flash("error")): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form action="actions/register_action.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full name</label>
                            <input name="name" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input name="username" required class="form-control" minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+">
                            <small class="text-muted">Use letters, numbers, and underscore only.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" minlength="6" required class="form-control">
                        </div>
                        <button class="btn btn-primary w-100">Register</button>
                    </form>
                    <p class="text-center mt-3 mb-0">
                        Already have an account?
                        <a href="login.php" class="text-decoration-none fw-semibold">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include "footer.php"; ?>
</body>
</html>