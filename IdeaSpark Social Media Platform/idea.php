<?php
require_once 'includes/db_connect.php';
session_start();

// CSRF token is created once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Only logged in users can post comments
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    // Idea id is taken from query string
    $idea_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $user_liked = false;

    if (isset($_SESSION['user_id']) && $idea_id > 0) {
        $stmtLiked = $mysqli->prepare('SELECT 1 FROM likes WHERE idea_id = ? AND user_id = ? LIMIT 1');
        if ($stmtLiked) {
            $uid = (int)$_SESSION['user_id'];
            $stmtLiked->bind_param('ii', $idea_id, $uid);
            $stmtLiked->execute();
            $resLiked = $stmtLiked->get_result();
            if ($resLiked && $resLiked->num_rows === 1) {
                $user_liked = true;
            }
            $stmtLiked->close();
        }
    }

    // Read comment body
    $comment_body = trim($_POST['comment_body'] ?? '');

    if ($idea_id <= 0) {
        header('Location: feed.php');
        exit;
    }

    // Insert comment if conditions fulfilled
    if ($comment_body !== '' && strlen($comment_body) <= 1000) {

        $stmt = $mysqli->prepare(
            'INSERT INTO comments (idea_id, user_id, body) VALUES (?, ?, ?)'
        );

        if ($stmt) {
            $user_id = (int)$_SESSION['user_id'];
            $stmt->bind_param('iis', $idea_id, $user_id, $comment_body);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirect to prevent form resubmission on refresh
    header('Location: idea.php?id=' . $idea_id);
    exit;
}
include 'includes/header.php';

// Load idea page (GET request)
$idea_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idea_id <= 0) {
    echo '<div id="main-content" class="container mt-5">';
    echo '<div class="alert alert-danger">Invalid idea.</div>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}

// Fetch the idea and author
$stmt = $mysqli->prepare(
    "SELECT
        ideas.id,
        ideas.title,
        ideas.body,
        ideas.image_path,
        ideas.created_at,
        users.username,
        users.id AS author_id
     FROM ideas
     JOIN users ON users.id = ideas.user_id
     WHERE ideas.id = ?
     LIMIT 1"
);

if (!$stmt) {
    echo '<div id="main-content" class="container mt-5">';
    echo '<div class="alert alert-danger">Could not load idea.</div>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}

$stmt->bind_param('i', $idea_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    echo '<div id="main-content" class="container mt-5">';
    echo '<div class="alert alert-warning">Idea not found.</div>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}

$idea = $result->fetch_assoc();
$stmt->close();

// Like and comment counts
$like_count = 0;
$comment_count = 0;

$stmtLikes = $mysqli->prepare("SELECT COUNT(*) AS c FROM likes WHERE idea_id = ?");
if ($stmtLikes) {
    $stmtLikes->bind_param('i', $idea_id);
    $stmtLikes->execute();
    $r = $stmtLikes->get_result();
    if ($r) {
        $like_count = (int)$r->fetch_assoc()['c'];
    }
    $stmtLikes->close();
}

$stmtCommentsCount = $mysqli->prepare("SELECT COUNT(*) AS c FROM comments WHERE idea_id = ?");
if ($stmtCommentsCount) {
    $stmtCommentsCount->bind_param('i', $idea_id);
    $stmtCommentsCount->execute();
    $r = $stmtCommentsCount->get_result();
    if ($r) {
        $comment_count = (int)$r->fetch_assoc()['c'];
    }
    $stmtCommentsCount->close();
}

// fetch comments and commenter usernames
$stmtC = $mysqli->prepare(
    "SELECT
        comments.id,
        comments.body,
        comments.created_at,
        users.username,
        users.id AS user_id
     FROM comments
     JOIN users ON users.id = comments.user_id
     WHERE comments.idea_id = ?
     ORDER BY comments.created_at ASC"
);

$comments = [];
if ($stmtC) {
    $stmtC->bind_param('i', $idea_id);
    $stmtC->execute();
    $r = $stmtC->get_result();
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $comments[] = $row;
        }
    }
    $stmtC->close();
}
?>

<div id="main-content" class="container mt-5">

    <!-- Idea Header -->
    <section class="mb-4">
        <a href="feed.php" class="small d-inline-block mb-2">&larr; Back to all ideas</a>

        <h1 class="h3 mb-2"><?php echo htmlspecialchars($idea['title']); ?></h1>

        <p class="small text-muted mb-2">
            Posted by
            <a href="profile.php?user_id=<?php echo (int)$idea['author_id']; ?>">
                <?php echo htmlspecialchars($idea['username']); ?>
            </a>
            on <?php echo htmlspecialchars(date('d M Y', strtotime($idea['created_at']))); ?>
        </p>

        <?php if (isset($_SESSION['user_id']) && ((int)$_SESSION['user_id'] === (int)$idea['author_id'] || (int)($_SESSION['user_level'] ?? 1) >= 2)): ?>
            <form method="post" action="delete_idea.php"
                onsubmit="return confirm('Delete this idea? This cannot be undone.');">
                <input type="hidden" name="idea_id" value="<?php echo (int)$idea_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete idea</button>
            </form>
        <?php endif; ?>
    </section>


    <!-- Body -->
    <section class="mb-4">
        <div class="card">
            <div class="card-body">

                <?php if (!empty($idea['image_path'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($idea['image_path']); ?>"
                        class="img-fluid rounded mb-3"
                        alt="Uploaded image for this idea"
                    >
                <?php endif; ?>

                <p class="mb-0">
                    <?php echo nl2br(htmlspecialchars($idea['body'])); ?>
                </p>

            </div>
        </div>
    </section>

    <!-- Idea actions -->
    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <button
                    class="btn btn-sm <?php echo $user_liked ? 'btn-primary' : 'btn-outline-primary'; ?> like-button"
                    type="button"
                    data-idea-id="<?php echo (int)$idea_id; ?>"
                    data-liked="<?php echo $user_liked ? 'true' : 'false'; ?>"
                >
                    <?php echo $user_liked ? 'Liked' : 'Like'; ?>
                </button>

                <span class="small ms-2 like-count"><?php echo (int)$like_count; ?></span>


            </div>
            <div class="small text-muted">
                <?php echo (int)$comment_count; ?> comments
            </div>
        </div>
    </section>

    <!-- comments -->
    <section class="mb-4">
        <h2 class="h4 mb-3">Comments</h2>

        <?php if (count($comments) === 0): ?>
            <p class="text-muted">No comments yet. Be the first to reply.</p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="mb-3">
                    <div class="border rounded-3 p-3">
                        <p class="mb-1">
                            <strong>
                                <a href="profile.php?user_id=<?php echo (int)$c['user_id']; ?>">
                                    <?php echo htmlspecialchars($c['username']); ?>
                                </a>
                            </strong>
                            <span class="small text-muted">
                                · <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($c['created_at']))); ?>
                            </span>
                        </p>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($c['body'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Add Comment-->
    <section class="mb-5">
        <h2 class="h5 mb-3">Add a comment</h2>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-info">
                You need to <a href="login.php">log in</a> to comment.
            </div>
        <?php else: ?>
            <form method="post" action="idea.php?id=<?php echo (int)$idea_id; ?>">
                <div class="mb-3">
                    <label for="comment_body" class="form-label">Your comment</label>
                    <textarea
                        class="form-control"
                        id="comment_body"
                        name="comment_body"
                        rows="3"
                        placeholder="Share a suggestion, question, or improvement for this idea."
                        required
                    ></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Post comment</button>
            </form>
        <?php endif; ?>
    </section>

</div>

<?php include 'includes/footer.php'; ?>
