<?php
// Connect with the database 
require_once 'includes/db_connect.php';
session_start();

// CSRF token is generated once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// profile.php?user_id=123 => public view for any user
$view_user_id = 0;

if (isset($_GET['user_id']) && (int)$_GET['user_id'] > 0) {
    $view_user_id = (int)$_GET['user_id'];
} else {
    // No user_id given: assume dashboard (own profile)
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $view_user_id = (int)$_SESSION['user_id'];
}

# Debug1: Seeing own profile
$is_own_profile = (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $view_user_id);

$errors = [];
$success_message = '';

// Handle new idea submission on own dashboard
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Defensive input handling
    $title = trim($_POST['idea_title'] ?? '');
    $body  = trim($_POST['idea_body'] ?? '');

    // Server-side validation
    if ($title === '') {
        $errors[] = 'Idea title is required.';
    } elseif (strlen($title) > 150) {
        $errors[] = 'Idea title must be 150 characters or fewer.';
    }

    if ($body === '') {
        $errors[] = 'Idea description is required.';
    }

    // Optional image upload
    $image_path = null;

    if (isset($_FILES['idea_image']) && $_FILES['idea_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['idea_image']['error'] === UPLOAD_ERR_OK) {

            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $mime = mime_content_type($_FILES['idea_image']['tmp_name']);

            if (!in_array($mime, $allowed, true)) {
                $errors[] = 'Image must be JPG, PNG, or WEBP.';
            } else {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                // Generate a unique filename to avoid collisions
                $ext = pathinfo($_FILES['idea_image']['name'], PATHINFO_EXTENSION);
                $safe_name = 'idea_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                $target = $upload_dir . $safe_name;
                //temp location to final destination
                if (move_uploaded_file($_FILES['idea_image']['tmp_name'], $target)) {
                    $image_path = $target;
                } else {
                    $errors[] = 'Could not save uploaded image.';
                }
            }

        } else {
            $errors[] = 'There was a problem uploading the image.';
        }
    }
    // Insret idea if validation and other checks passed
    if (empty($errors)) {
        $stmt = $mysqli->prepare('INSERT INTO ideas (user_id, title, body, image_path) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            $errors[] = 'Could not prepare idea insert query.';
        } else {
            $uid = (int)$_SESSION['user_id'];
            $stmt->bind_param('isss', $uid, $title, $body, $image_path);

            if ($stmt->execute()) {
                $success_message = 'Idea posted successfully.';
            } else {
                $errors[] = 'Could not post idea.';
            }

            $stmt->close();
        }
    }
}

include 'includes/header.php';

// Load profile user info
$profile_user = null;

$stmtP = $mysqli->prepare('SELECT id, username, user_level, created_at FROM users WHERE id = ? LIMIT 1');
if ($stmtP) {
    $stmtP->bind_param('i', $view_user_id);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    if ($resP && $resP->num_rows === 1) {
        $profile_user = $resP->fetch_assoc();
    }
    $stmtP->close();
}

if (!$profile_user) {
    echo '<div id="main-content" class="container mt-5">';
    echo '<div class="alert alert-danger">User not found.</div>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}

// Stats for DashB
$ideas_posted = 0;
$comments_written = 0;
$total_likes_on_ideas = 0;

$stmtA = $mysqli->prepare('SELECT COUNT(*) AS c FROM ideas WHERE user_id = ?');
if ($stmtA) {
    $stmtA->bind_param('i', $view_user_id);
    $stmtA->execute();
    $r = $stmtA->get_result();
    if ($r) {
        $ideas_posted = (int)$r->fetch_assoc()['c'];
    }
    $stmtA->close();
}

$stmtB = $mysqli->prepare('SELECT COUNT(*) AS c FROM comments WHERE user_id = ?');
if ($stmtB) {
    $stmtB->bind_param('i', $view_user_id);
    $stmtB->execute();
    $r = $stmtB->get_result();
    if ($r) {
        $comments_written = (int)$r->fetch_assoc()['c'];
    }
    $stmtB->close();
}

// total likes across all ideas owned by this user
$stmtC = $mysqli->prepare('
    SELECT COUNT(*) AS c
    FROM likes
    JOIN ideas ON ideas.id = likes.idea_id
    WHERE ideas.user_id = ?
');
if ($stmtC) {
    $stmtC->bind_param('i', $view_user_id);
    $stmtC->execute();
    $r = $stmtC->get_result();
    if ($r) {
        $total_likes_on_ideas = (int)$r->fetch_assoc()['c'];
    }
    $stmtC->close();
}

// count ideas posted by user
// note: counts done with subqueries for simplicity.
$ideas = [];

$sqlIdeas = "
    SELECT
        ideas.id,
        ideas.title,
        ideas.body,
        ideas.created_at,
        (SELECT COUNT(*) FROM comments WHERE comments.idea_id = ideas.id) AS comment_count,
        (SELECT COUNT(*) FROM likes WHERE likes.idea_id = ideas.id) AS like_count
    FROM ideas
    WHERE ideas.user_id = ?
    ORDER BY ideas.created_at DESC
    LIMIT 12
";

$stmtI = $mysqli->prepare($sqlIdeas);
if ($stmtI) {
    $stmtI->bind_param('i', $view_user_id);
    $stmtI->execute();
    $resI = $stmtI->get_result();
    if ($resI) {
        while ($row = $resI->fetch_assoc()) {
            $ideas[] = $row;
        }
    }
    $stmtI->close();
}

// Simple avatar initials from username
$uname = (string)$profile_user['username'];
$initials = strtoupper(substr($uname, 0, 2));
?>

<div id="main-content" class="container mt-5">

<!-- Profile Header -->
<div class="row mb-4">
    <div class="col-md-8 d-flex align-items-center">
        <div class="me-3">
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                <span class="fw-semibold"><?php echo htmlspecialchars($initials); ?></span>
            </div>
        </div>
        <div>
            <h1 class="h3 mb-1"><?php echo htmlspecialchars($profile_user['username']); ?></h1>
            <p class="mb-1 small text-muted">
                Joined: <?php echo htmlspecialchars(date('F Y', strtotime($profile_user['created_at']))); ?>
                · Level <?php echo (int)$profile_user['user_level']; ?>
                <?php if ((int)$profile_user['user_level'] >= 2): ?>
                    (moderator)
                <?php endif; ?>
            </p>
            <p class="mb-0 small">
                <!-- bio is not stored yet, Note: if time allows -->
                Bio: brainstorms small tools and quick prototypes.
            </p>
        </div>
    </div>

    <div class="col-md-4 mt-3 mt-md-0 text-md-end">
        <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2">Back to home</a><br>

        <?php if ($is_own_profile): ?>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Log out</a>
        <?php else: ?>
            <a href="feed.php" class="btn btn-outline-secondary btn-sm">Back to feed</a>
        <?php endif; ?>
    </div>
</div>

<!-- Profile stats -->
<div class="row mb-4">
    <div class="col-md-4 mb-2 mb-md-0">
        <div class="p-3 border rounded-3">
            <p class="mb-1 small text-muted">Ideas posted</p>
            <p class="mb-0 fs-5"><?php echo (int)$ideas_posted; ?></p>
        </div>
    </div>
    <div class="col-md-4 mb-2 mb-md-0">
        <div class="p-3 border rounded-3">
            <p class="mb-1 small text-muted">Comments written</p>
            <p class="mb-0 fs-5"><?php echo (int)$comments_written; ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded-3">
            <p class="mb-1 small text-muted">Total likes on ideas</p>
            <p class="mb-0 fs-5"><?php echo (int)$total_likes_on_ideas; ?></p>
        </div>
    </div>
</div>

<?php if ($is_own_profile): ?>
<!-- New Idea Form -->
<section class="mb-5">
    <h2 class="h4 mb-3">Post a new idea</h2>

    <?php if ($success_message !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="profile.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="idea_title" class="form-label">Idea title</label>
            <input
                type="text"
                class="form-control"
                id="idea_title"
                name="idea_title"
                placeholder="Give your idea a short, clear title"
            >
        </div>

        <div class="mb-3">
            <label for="idea_body" class="form-label">Idea description</label>
            <textarea
                class="form-control"
                id="idea_body"
                name="idea_body"
                rows="4"
                placeholder="Describe your idea in one short paragraph."
            ></textarea>
        </div>

        <div class="mb-3">
            <label for="idea_image" class="form-label">Optional image</label>
            <input
                type="file"
                class="form-control"
                id="idea_image"
                name="idea_image"
            >
            <div class="form-text">You can attach a simple sketch or mockup if you have one.</div>
        </div>

        <button type="submit" class="btn btn-primary">Post idea</button>
    </form>
</section>
<?php endif; ?>

<!-- User Ideas -->
<section class="mb-5">
    <h2 class="h4 mb-3">
        <?php echo $is_own_profile ? 'Your ideas' : 'Ideas'; ?>
    </h2>

    <div class="row g-4">

        <?php if (count($ideas) === 0): ?>
            <div class="col-12">
                <p class="text-muted mb-0">No ideas posted yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($ideas as $idea): ?>
                <div class="col-md-6">
                    <article class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title h5"><?php echo htmlspecialchars($idea['title']); ?></h3>

                            <p class="card-text small text-muted mb-1">
                                Posted on <?php echo htmlspecialchars(date('d M Y', strtotime($idea['created_at']))); ?>
                                · <?php echo (int)$idea['like_count']; ?> likes
                                · <?php echo (int)$idea['comment_count']; ?> comments
                            </p>

                            <p class="card-text">
                                <?php echo htmlspecialchars(mb_strimwidth($idea['body'], 0, 160, '...')); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <a href="idea.php?id=<?php echo (int)$idea['id']; ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                </div>

                                <?php if ($is_own_profile): ?>
                                    <form method="post" action="delete_idea.php" class="d-inline"
                                          onsubmit="return confirm('Delete this idea? This cannot be undone.');">
                                        <input type="hidden" name="idea_id" value="<?php echo (int)$idea['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>

</div>

<?php include 'includes/footer.php'; ?>

