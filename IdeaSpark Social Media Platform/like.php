<?php
require_once 'includes/db_connect.php';
session_start();

// Endpoint is called via AJAX/fetch, so return JSON
header('Content-Type: application/json');

// must be logged in to like/unlike
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

// Read inputs from POST
$idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;

// Default action is like if not provided
$action  = $_POST['action'] ?? 'like'; // expected: like pipe unlike

// Basic sanity check
if ($idea_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'bad_idea_id']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Perform like or unlike action
if ($action === 'unlike') {

    // Remove the like for this user and idea
    $stmt = $mysqli->prepare('DELETE FROM likes WHERE idea_id = ? AND user_id = ?');
    if ($stmt) {
        $stmt->bind_param('ii', $idea_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

} else {

    // Add a like 
    // NOTE: This assumes a UNIQUE (idea_id, user_id) constraint in DB
    // Avoids duplicate key errors.
    $stmt = $mysqli->prepare('INSERT IGNORE INTO likes (idea_id, user_id) VALUES (?, ?)');
    if ($stmt) {
        $stmt->bind_param('ii', $idea_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Return updated like count so UI can refresh instantly
$stmt2 = $mysqli->prepare('SELECT COUNT(*) AS c FROM likes WHERE idea_id = ?');
$like_count = 0;

if ($stmt2) {
    $stmt2->bind_param('i', $idea_id);
    $stmt2->execute();

    $res = $stmt2->get_result();
    if ($res) {
        $like_count = (int)$res->fetch_assoc()['c'];
    }

    $stmt2->close();
}

echo json_encode(['ok' => true, 'likes' => $like_count]);
