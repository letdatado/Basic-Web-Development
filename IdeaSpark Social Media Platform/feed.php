<?php
require_once 'includes/db_connect.php';
session_start();

include 'includes/header.php';

// ----------------------------
// Fetch latest ideas for feed
// -------------------------
// note: Subqueries compute like/comment counts per idea.
// Can be optimized later with JOIN + GROUP BY.
$sql = "
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
    LIMIT 20
";

// Simple query execution 
$result = $mysqli->query($sql);
?>

<div id="main-content" class="container mt-5">
    <!-- Feed Header -->
    <section class="mb-4">
        <h1 class="h3 mb-1">All ideas</h1>
        <p class="small text-muted mb-0">
            Browse recent ideas from everyone on IdeaSpark. Use search if you’re looking for something specific.
        </p>
    </section>

    <!-- Sort / Filter Row (sample for now) -->
    <!-- NOTE: These controls are not completed -->
    <section class="mb-4">
        <form class="row gy-2 gx-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <label class="form-label small mb-1" for="sort_by">Sort by</label>
                <select id="sort_by" class="form-select form-select-sm">
                    <option value="latest">Latest first</option>
                    <option value="popular">Most liked</option>
                    <option value="commented">Most commented</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label small mb-1" for="filter_category">Category</label>
                <select id="filter_category" class="form-select form-select-sm">
                    <option value="all">All categories</option>
                    <option value="productivity">Productivity</option>
                    <option value="study">Study</option>
                    <option value="tools">Tools</option>
                </select>
            </div>
        </form>
    </section>

    <!-- Ideas Feed (dynamic) -->
    <section class="mb-5">
        <div class="row g-4">

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <article class="card h-100">
                            <div class="card-body">

                                <h2 class="h5 card-title">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h2>

                                <p class="card-text small text-muted mb-1">
                                    by
                                    <a href="profile.php?user_id=<?php echo (int)$row['author_id']; ?>">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                    </a>
                                    · <?php echo (int)$row['like_count']; ?> likes
                                    · <?php echo (int)$row['comment_count']; ?> comments
                                </p>

                                <p class="card-text">
                                    <?php echo htmlspecialchars(mb_strimwidth($row['body'], 0, 140, '...')); ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <a href="idea.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View idea
                                    </a>
                                    <span class="small text-muted">
                                        <?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?>
                                    </span>
                                </div>

                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted mb-0">No ideas yet. Be the first to post one!</p>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Pagination -->
    <!-- NOTE: Under progress -->
    <div class="d-flex justify-content-center mb-5">
        <nav aria-label="Idea feed pages">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
                <li class="page-item active"><span class="page-link">1</span></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
