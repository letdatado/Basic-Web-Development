<?php
require_once 'includes/db_connect.php';

// Shared header 
include 'includes/header.php';

// Quick stats for homepage box
// Note: a simple snapshot and uses totals across the platform.
$idea_total = 0;
$comment_total = 0;
$like_total = 0;

$stmtIdeas = $mysqli->prepare("SELECT COUNT(*) AS c FROM ideas");
if ($stmtIdeas) {
    $stmtIdeas->execute();
    $r = $stmtIdeas->get_result();
    if ($r) {
        $idea_total = (int)$r->fetch_assoc()['c'];
    }
    $stmtIdeas->close();
}

$stmtComments = $mysqli->prepare("SELECT COUNT(*) AS c FROM comments");
if ($stmtComments) {
    $stmtComments->execute();
    $r = $stmtComments->get_result();
    if ($r) {
        $comment_total = (int)$r->fetch_assoc()['c'];
    }
    $stmtComments->close();
}

$stmtLikes = $mysqli->prepare("SELECT COUNT(*) AS c FROM likes");
if ($stmtLikes) {
    $stmtLikes->execute();
    $r = $stmtLikes->get_result();
    if ($r) {
        $like_total = (int)$r->fetch_assoc()['c'];
    }
    $stmtLikes->close();
}

// Recent ideas 
$recent = [];

$sqlRecent = "
    SELECT
        ideas.id,
        ideas.title,
        ideas.body,
        ideas.created_at,
        users.username,
        users.id AS author_id,
        (SELECT COUNT(*) FROM comments WHERE comments.idea_id = ideas.id) AS comment_count,
        (SELECT COUNT(*) FROM likes WHERE likes.idea_id = ideas.id) AS like_count
    FROM ideas
    JOIN users ON users.id = ideas.user_id
    ORDER BY ideas.created_at DESC
    LIMIT 3
";

$resRecent = $mysqli->query($sqlRecent);
if ($resRecent) {
    while ($row = $resRecent->fetch_assoc()) {
        $recent[] = $row;
    }
}
?>

<div id="main-content" class="container mt-5">

    <!-- Hero Section -->
    <!-- Landing page messaging and primary calls to action -->
    <div class="row align-items-center mb-5">
        <div class="col-md-7">
            <h1 class="mb-3">IdeaSpark</h1>
            <p class="lead">Share one-paragraph ideas and get quick feedback.</p>
            <!-- Debug: Offer account creation when logged out -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-primary me-2">Create an account</a>
                <a href="feed.php" class="btn btn-outline-secondary">Browse ideas</a>
            <?php else: ?>
                <a href="profile.php" class="btn btn-primary me-2">Go to your profile</a>
                <a href="feed.php" class="btn btn-outline-secondary">Browse ideas</a>
            <?php endif; ?>
        </div>

        <div class="col-md-5 text-md-end mt-4 mt-md-0">
            <!-- Note: Under progress -->
            <div class="p-4 border rounded-3">
                <p class="mb-1 fw-semibold">Today’s activity</p>

                <?php if ($idea_total > 0 || $comment_total > 0 || $like_total > 0): ?>
                    <p class="mb-0 small text-muted">
                        <?php echo (int)$idea_total; ?> ideas ·
                        <?php echo (int)$comment_total; ?> comments ·
                        <?php echo (int)$like_total; ?> likes
                    </p>
                <?php else: ?>
                    <p class="mb-0 small text-muted">No activity to show yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Carousel Section - Bootstrap -->
    <div id="ideasparkCarousel" class="carousel slide mb-5" data-bs-ride="carousel">

        <!-- Carousel indicators -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#ideasparkCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#ideasparkCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#ideasparkCarousel" data-bs-slide-to="2"></button>
        </div>

        <!-- Carousel inner -->
        <div class="carousel-inner rounded-3">
            <div class="carousel-item active">
                <img src="img/slide1.jpg" class="d-block w-100" alt="People brainstorming ideas together">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Capture ideas instantly</h5>
                    <p>Post simple one-paragraph concepts anytime.</p>
                </div>
            </div>

            <div class="carousel-item">
                <img src="img/slide2.jpg" class="d-block w-100" alt="Students collaborating on projects">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Get meaningful feedback</h5>
                    <p>See how others expand and refine your ideas.</p>
                </div>
            </div>

            <div class="carousel-item">
                <img src="img/slide3.jpg" class="d-block w-100" alt="Lightbulb representing new ideas">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Collaborate effortlessly</h5>
                    <p>Find people who are inspired by the same sparks.</p>
                </div>
            </div>
        </div>

        <!-- Carousel controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#ideasparkCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#ideasparkCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>

    </div>

    <!-- Recent Ideas -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Recent Ideas</h2>
            <a href="feed.php" class="small">View all</a>
        </div>

        <div class="row g-4">
            <!-- NOTE: Sample data. Under progress -->
            <?php if (count($recent) > 0): ?>
                <?php foreach ($recent as $idea): ?>
                    <div class="col-md-4">
                        <article class="card h-100">
                            <div class="card-body">
                                <h3 class="card-title h5">
                                    <?php echo htmlspecialchars($idea['title']); ?>
                                </h3>
                                <p class="card-text small text-muted">
                                    by
                                    <a href="profile.php?user_id=<?php echo (int)$idea['author_id']; ?>">
                                        <?php echo htmlspecialchars($idea['username']); ?>
                                    </a>
                                    · <?php echo (int)$idea['like_count']; ?> likes
                                    · <?php echo (int)$idea['comment_count']; ?> comments
                                </p>
                                <p>
                                    <?php echo htmlspecialchars(mb_strimwidth($idea['body'], 0, 120, '...')); ?>
                                </p>
                                <a href="idea.php?id=<?php echo (int)$idea['id']; ?>" class="btn btn-sm btn-outline-primary">View idea</a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted mb-0">No ideas as of now. Create an account and post the first spark.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Search Section -->
    <section class="mb-5">
        <h2 class="h4 mb-3">Find ideas or people</h2>

        <form class="row gy-2 gx-2 align-items-center" action="search.php" method="get">
            <div class="col-sm-8 col-md-9">
                <input
                    type="text"
                    class="form-control"
                    name="q"
                    placeholder="Search by keyword or username..."
                >
            </div>
            <div class="col-sm-4 col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Search</button>
            </div>
        </form>
    </section>

</div>

<?php
// Shared footer (loads scripts and closes HTML)
include 'includes/footer.php';
?>
