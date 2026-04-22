<?php require_once __DIR__ . "/helpers.php"; ?>
<?php
$navUser = null;
if (is_logged_in()) {
    $navStmt = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id = ?");
    $navStmt->bind_param("i", $_SESSION["user_id"]);
    $navStmt->execute();
    $navUser = $navStmt->get_result()->fetch_assoc();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">ShowCase</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Explore</a></li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex gap-2 align-items-center">
                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <button
                            class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Open profile menu"
                        >
                            <?php if (!empty($navUser["profile_picture"]) && file_exists(__DIR__ . "/uploads/" . $navUser["profile_picture"])): ?>
                                <img
                                    src="uploads/<?= e($navUser["profile_picture"]) ?>"
                                    alt="Profile"
                                    class="rounded-circle border border-light"
                                    style="width:38px;height:38px;object-fit:cover;"
                                >
                            <?php else: ?>
                                <div
                                    class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold border border-light"
                                    style="width:38px;height:38px;"
                                >
                                    <?= e(strtoupper(substr($navUser["username"] ?? "U", 0, 1))) ?>
                                </div>
                            <?php endif; ?>
                            <span class="text-white small d-none d-md-inline">@<?= e($navUser["username"] ?? "user") ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">@<?= e($navUser["username"] ?? "user") ?></h6></li>
                            <li><a class="dropdown-item" href="profile.php">View Profile</a></li>
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>