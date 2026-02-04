<?php
require_once 'includes/db_connect.php';
session_start();

$errors = [];

// If logged in, go to profile to avoid re-login
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Only process credentials on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {

        // Prepared statement prevents SQL injection
        $stmt = $mysqli->prepare(
            'SELECT id, username, password_hash, user_level FROM users WHERE username = ? LIMIT 1'
        );

        if (!$stmt) {
            // In production I might log $mysqli error but show generic message to user
            $errors[] = 'Could not prepare login query.';
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // Only a row should match (username should be UNIQUE)
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // verify hash rather than comparing plain text
                if (password_verify($password, $user['password_hash'])) {

                    // If login success: store session info used across the site
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_level'] = (int)$user['user_level'];

                    // Redirect after setting session to avoid form resubmission on refresh
                    header('Location: profile.php');
                    exit;

                } else {
                    // Keep error generic to avoid account enumeration
                    $errors[] = 'Incorrect username or password.';
                }

            } else {
                // Same generic error if userid not found
                $errors[] = 'Incorrect username or password.';
            }

            $stmt->close();
        }
    }
}

include 'includes/header.php';
?>

<div id="main-content" class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <h1 class="h3 mb-4">Log in</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <!-- Escape messages in case any become dynamic later -->
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- novalidate disables browser validation bubbles, tf server messages are shown above -->
            <form method="post" action="login.php" novalidate>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">Log in</button>
                <a href="register.php" class="btn btn-link">Create an account</a>

            </form>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
