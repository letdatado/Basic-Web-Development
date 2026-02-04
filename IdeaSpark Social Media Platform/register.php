<?php
// Connect with the database 
require_once 'includes/db_connect.php';
session_start();

// Collect all errors here to show them together
$errors = [];
$success = false;

// Handle form submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pull user input defensively with preprocessing
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation ----
    // Keep validation server-side even if I add client-side checks later.
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must be 50 chars or fewer.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email id.';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email must be 100 chars or fewer.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password should be at least 8 chars long.';
    }

    // Confirm password separately so the user gets a specific message
    if ($confirm_password === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Insert user if no validation errors
    if (empty($errors)) {

        // Hash passwords using PHP’s built-in safe defaults (bcrypt/argon depending on version)
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepared statements prevent SQL injection
        $stmt = $mysqli->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );

        if (!$stmt) {
            // If you want more detail during development, you can log $mysqli->error
            $errors[] = 'Could not prepare registration query.';
        } else {
            $stmt->bind_param('sss', $username, $email, $password_hash);

            if ($stmt->execute()) {
                $success = true;
            } else {
                // 1062 is MySQL duplicate key (usually a UNIQUE index)
                // NOTE: This message assumes the duplicate is username; see suggestions below.
                if ($stmt->errno === 1062) {
                    $errors[] = 'That username is already taken. Please choose another.';
                } else {
                    $errors[] = 'There was a problem creating your account.';
                }
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

            <h1 class="h3 mb-4">Create an account</h1>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Your account has been created. You can now
                    <a href="login.php">log in</a>.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <!-- Escape errors in case they contain dynamic content -->
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- novalidate disables browser-native validation UI, rely on your own messages -->
            <form method="post" action="register.php" novalidate>

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
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                        maxlength="100"
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
                    <div class="form-text">
                        Use at least 8 chars.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="confirm_password"
                        name="confirm_password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
                <a href="login.php" class="btn btn-link">Already have an account?</a>

            </form>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
