<?php
require_once 'includes/db_connect.php';
session_start();

include 'includes/header.php';

// Basic search across ideas + users
$q = trim($_GET['q'] ?? '');
$q_like = '%' . $q . '%';

// Results containers
$user_results = [];
$idea_results = [];

// Only run queries if something was typed
if ($q !== '') {

    // ----------------------------
    // Search users (by username/id)
    // ----------------------------
    $stmtU = $mysqli->prepare("
        SELECT id, username, created_at, user_level
        FROM users
        WHERE username LIKE ?
        ORDER BY username ASC
        LIMIT 10
    ");

    if ($stmtU) {
        $stmtU->bind_param('s', $q_like);
        $stmtU->execute();
        $resU = $stmtU->get_result();

        if ($resU) {
            while ($row = $resU->fetch_assoc()) {
                $user_results[] = $row;
            }
        }
        $stmtU->close();
    }

    // ----------------------------
    // Search ideas 
    // ----------------------------
    $stmtI = $mysqli->prepare("
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
        WHERE ideas.title LIKE ? OR ideas.body LIKE ?
        ORDER BY ideas.created_at DESC
        LIMIT 20
    ");

    if ($stmtI) {
        $stmtI->bind_param('ss', $q_like, $q_like);
        $stmtI->execute();
        $resI = $stmtI->get_result();

        if ($resI) {
            while ($row = $resI->fetch_assoc()) {
                $idea_results[] = $row;
            }
        }
        $stmtI->close();
    }
}
?>

<div id="main-content" class="container mt-5">

    <!-- Search Header -->
    <section class="mb-4">
        <h1 class="h3 mb-1">Search</h1>
        <p class="small text-muted mb-0">
            Search by keyword to find ideas, or type a username to find people.
        </p>
    </section>

    <!-- Search Form -->
    <section class="mb-4">
        <form class="row gy-2 gx-2 align-items-center" action="search.php" method="get">
            <div class="col-sm-8 col-md-9">
                <label class="visually-hidden" for="q">Search</label>
                <input
                    type="text"
                    class="form-control"
                    id="q"
                    name="q"
                    value="<?php echo htmlspecialchars($q); ?>"
                    placeholder="Search by keyword or username..."
                >
            </div>
            <div class="col-sm-4 col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Search</button>
            </div>
        </form>
    </section>

    <?php if ($q === ''): ?>
        <div class="alert alert-info">
            Type something above to search. Example: a keyword from an idea title, or a username.
        </div>
    <?php else: ?>

        <!-- People Results -->
        <section class="mb-5">
            <h2 class="h5 mb-3">People</h2>

            <?php if (count($user_results) === 0): ?>
                <p class="text-muted mb-0">No users matched "<?php echo htmlspecialchars($q); ?>".</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($user_results as $u): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                           href="profile.php?user_id=<?php echo (int)$u['id']; ?>">
                            <span>
                                <?php echo htmlspecialchars($u['username']); ?>
                                <?php if ((int)$u['user_level'] >= 2): ?>
                                    <span class="badge text-bg-warning ms-2">moderator</span>
                                <?php endif; ?>
                            </span>
                            <span class="small text-muted">
                                joined <?php echo htmlspecialchars(date('d M Y', strtotime($u['created_at']))); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Idea Results -->
        <section class="mb-5">
            <h2 class="h5 mb-3">Ideas</h2>

            <?php if (count($idea_results) === 0): ?>
                <p class="text-muted mb-0">No ideas matched "<?php echo htmlspecialchars($q); ?>".</p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($idea_results as $idea): ?>
                        <div class="col-md-4">
                            <article class="card h-100">
                                <div class="card-body">

                                    <h3 class="card-title h6 mb-2">
                                        <?php echo htmlspecialchars($idea['title']); ?>
                                    </h3>

                                    <p class="card-text small text-muted mb-2">
                                        by
                                        <a href="profile.php?user_id=<?php echo (int)$idea['author_id']; ?>">
                                            <?php echo htmlspecialchars($idea['username']); ?>
                                        </a>
                                        · <?php echo (int)$idea['like_count']; ?> likes
                                        · <?php echo (int)$idea['comment_count']; ?> comments
                                    </p>

                                    <p class="card-text">
                                        <?php echo htmlspecialchars(mb_strimwidth($idea['body'], 0, 120, '...')); ?>
                                    </p>

                                    <a href="idea.php?id=<?php echo (int)$idea['id']; ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        View idea
                                    </a>

                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
