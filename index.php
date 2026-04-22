<?php
require_once "helpers.php";

$query = "
    SELECT posts.id, posts.title, posts.description, posts.image, posts.post_type, posts.price, posts.job_link, posts.created_at,
           users.name AS author_name, users.username AS author_username, categories.name AS category_name
    FROM posts
    JOIN users ON posts.user_id = users.id
    JOIN categories ON posts.category_id = categories.id
    ORDER BY posts.created_at DESC
";
$result = $conn->query($query);
$posts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

$totalPosts = (int) ($conn->query("SELECT COUNT(*) AS c FROM posts")->fetch_assoc()["c"] ?? 0);
$totalUsers = (int) ($conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()["c"] ?? 0);
$totalEvents = (int) ($conn->query("SELECT COUNT(*) AS c FROM posts WHERE post_type = 'event'")->fetch_assoc()["c"] ?? 0);

$eventsStmt = $conn->query("
    SELECT posts.id, posts.title, events.event_date, events.event_time
    FROM events
    JOIN posts ON posts.id = events.post_id
    ORDER BY events.event_date ASC, events.event_time ASC
    LIMIT 3
");
$upcomingEvents = [];
if ($eventsStmt) {
    while ($event = $eventsStmt->fetch_assoc()) {
        $upcomingEvents[] = $event;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowCase | Explore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-bg {
            background: linear-gradient(120deg, #1d2671, #6c2b8e, #c33764);
            background-size: 200% 200%;
            color: #fff;
            animation: gradientShift 10s ease infinite;
            position: relative;
            overflow: hidden;
        }
        .hero-bg::before {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            top: -180px;
            right: -120px;
        }
        .hero-bg::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            bottom: -180px;
            left: -100px;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .hero-content { position: relative; z-index: 2; }
        .post-card { transition: transform .25s ease, box-shadow .25s ease; border-radius: 14px; }
        .post-card:hover { transform: translateY(-6px); box-shadow: 0 1rem 1.8rem rgba(0,0,0,.12); }
        .post-image { height: 180px; object-fit: cover; }
        .stat-card { border-radius: 14px; }
        .reveal { opacity: 0; transform: translateY(16px); transition: opacity .55s ease, transform .55s ease; }
        .reveal.active { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="bg-light">
<?php include "navbar.php"; ?>

<header class="hero-bg py-5 py-lg-6 mb-4">
    <div class="container hero-content py-3">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold">Build Your Future with Skills That Matter</h1>
                <p class="lead mb-4">ShowCase blends portfolio, marketplace, and community learning in one place.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= is_logged_in() ? "dashboard.php" : "register.php" ?>" class="btn btn-light btn-lg">Start Sharing</a>
                    <a href="#explore-posts" class="btn btn-outline-light btn-lg">Explore Posts</a>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container pb-4">
    <?php if ($success = get_flash("success")): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error = get_flash("error")): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="row g-3 mb-4 reveal">
        <div class="col-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-secondary mb-1">Total Posts</p>
                    <h4 class="mb-0"><?= $totalPosts ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-secondary mb-1">Creators</p>
                    <h4 class="mb-0"><?= $totalUsers ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-secondary mb-1">Live Events</p>
                    <h4 class="mb-0"><?= $totalEvents ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-secondary mb-1">Marketplace</p>
                    <h4 class="mb-0">Active</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm mb-4 reveal">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg-9">
                    <select id="typeFilter" class="form-select">
                        <option value="all">All Types</option>
                        <option value="skill">Skill</option>
                        <option value="course">Course</option>
                        <option value="event">Event</option>
                        <option value="job">Job</option>
                    </select>
                </div>
                <div class="col-lg-3 d-flex justify-content-lg-end">
                    <button id="resetFilter" class="btn btn-outline-secondary w-100 w-lg-auto">Reset</button>
                </div>
            </div>
        </div>
    </section>

    <?php if (count($upcomingEvents) > 0): ?>
        <section class="card border-0 shadow-sm mb-4 reveal">
            <div class="card-body">
                <h5 class="mb-3">Upcoming Events</h5>
                <div class="row g-3">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <h6><?= e($event["title"]) ?></h6>
                                <p class="small mb-1 text-secondary">Date: <?= e(date("d M Y", strtotime($event["event_date"]))) ?></p>
                                <p class="small mb-0 text-secondary">Time: <?= e(date("h:i A", strtotime($event["event_time"]))) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section id="explore-posts" class="reveal">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Explore Latest Posts</h3>
        <span id="resultsCount" class="text-muted small"></span>
    </div>
    <div class="row g-4" id="postsGrid">
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $row): ?>
                <div class="col-md-6 col-lg-4">
                    <div
                        class="card post-card h-100 border-0 shadow-sm post-item"
                        data-type="<?= e(strtolower($row["post_type"])) ?>"
                    >
                        <?php if (!empty($row["image"]) && file_exists(__DIR__ . "/uploads/" . $row["image"])): ?>
                            <img class="card-img-top post-image" src="uploads/<?= e($row["image"]) ?>" alt="<?= e($row["title"]) ?>">
                        <?php else: ?>
                            <div class="post-image d-flex align-items-center justify-content-center bg-secondary-subtle text-muted">No Image</div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge text-bg-primary"><?= e($row["category_name"]) ?></span>
                                <span class="badge text-bg-dark"><?= e(ucfirst($row["post_type"])) ?></span>
                            </div>
                            <h5 class="card-title"><?= e($row["title"]) ?></h5>
                            <p class="card-text text-muted"><?= e(mb_strimwidth($row["description"], 0, 120, "...")) ?></p>
                            <p class="small text-secondary mt-auto mb-2">
                                By <a class="text-decoration-none" href="user.php?u=<?= urlencode((string) $row["author_username"]) ?>"><?= e($row["author_name"]) ?></a>
                            </p>
                            <div class="d-flex gap-2">
                                <a href="post.php?id=<?= (int) $row["id"] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                <?php if ($row["post_type"] === "job" && !empty($row["job_link"])): ?>
                                    <?php if (is_logged_in()): ?>
                                        <a href="<?= e($row["job_link"]) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">Apply</a>
                                    <?php else: ?>
                                        <a href="login.php?next=<?= urlencode((string) $row["job_link"]) ?>" class="btn btn-primary btn-sm">Apply</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (is_logged_in()): ?>
                                        <a href="payment.php?post_id=<?= (int) $row["id"] ?>" class="btn btn-success btn-sm">Buy Now</a>
                                    <?php else: ?>
                                        <a href="login.php?next=<?= urlencode("payment.php?post_id=" . (int) $row["id"]) ?>" class="btn btn-success btn-sm">Buy Now</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">No posts yet. Be the first to share your expertise.</div>
            </div>
        <?php endif; ?>
    </div>
    </section>

    <section class="card border-0 shadow-sm mt-4 reveal">
        <div class="card-body text-center p-4">
            <h4>Ready to teach or showcase your service?</h4>
            <p class="text-muted mb-3">Create your first post and start building your reputation.</p>
            <a href="<?= is_logged_in() ? "dashboard.php" : "register.php" ?>" class="btn btn-primary">Create Your Post</a>
        </div>
    </section>
</main>

<?php include "footer.php"; ?>
<script>
    (function () {
        const posts = Array.from(document.querySelectorAll(".post-item"));
        const typeFilter = document.getElementById("typeFilter");
        const resetFilter = document.getElementById("resetFilter");
        const resultsCount = document.getElementById("resultsCount");

        function applyFilters() {
            const selectedType = (typeFilter?.value || "all").toLowerCase();
            let visible = 0;

            posts.forEach((item) => {
                const matchType = selectedType === "all" || item.dataset.type === selectedType;
                const show = matchType;
                item.parentElement.style.display = show ? "" : "none";
                if (show) visible += 1;
            });

            if (resultsCount) {
                resultsCount.textContent = visible + " result" + (visible === 1 ? "" : "s");
            }
        }

        if (typeFilter) typeFilter.addEventListener("change", applyFilters);
        if (resetFilter) {
            resetFilter.addEventListener("click", function () {
                if (typeFilter) typeFilter.value = "all";
                applyFilters();
            });
        }
        applyFilters();

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("active");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));
    })();
</script>
</body>
</html>