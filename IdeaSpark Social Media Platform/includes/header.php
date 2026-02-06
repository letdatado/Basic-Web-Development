<?php
// Start session safely so the navbar can read login state.
// ... some pages already start a session, so guard against double-starting.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Responsive viewport for mobile-first layouts -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Site specific styles -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Logo of the website -->
    <link rel="icon" type="image/svg+xml" href="img/favicon.svg">


    <title>IdeaSpark</title>
</head>
<body>

<!-- allows keyboard users to skip navigation -->
<a href="#main-content" class="visually-hidden-focusable">Skip to main content</a>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <!-- home link with icon-->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="img/icon.svg" alt="" width="28" height="28" class="me-2">
      IdeaSpark
    </a>

    <!-- Mobile nav toggle -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navMenu"
            aria-controls="navMenu"
            aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="feed.php">Ideas</a></li>
        <li class="nav-item"><a class="nav-link" href="search.php">Search</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Logged in navigation -->
          <li class="nav-item">
            <a class="nav-link" href="profile.php">
              <?php echo htmlspecialchars($_SESSION['username']); ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <!-- Guest navigation -->
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
