<?php
// Start the session, and clear it
session_start();

// Remove all session variables
session_unset();

// Destroy the session data on the server
session_destroy();

// Redirect back to public homepage after logout
header('Location: index.php');
exit;
