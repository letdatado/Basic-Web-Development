<?php
require_once 'includes/db_connect.php';
session_start();

// Only logged in users can delete ideas
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: feed.php');
    exit;
}

// Read inputs
$idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;
$token   = $_POST['csrf_token'] ?? '';

if ($idea_id <= 0) {
    header('Location: feed.php');
    exit;
}

// CSRF check
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header('Location: profile.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_level = isset($_SESSION['user_level']) ? (int)$_SESSION['user_level'] : 1;

// Fetch idea owner and image path for permission check etc
$stmt = $mysqli->prepare('SELECT user_id, image_path FROM ideas WHERE id = ? LIMIT 1');
if (!$stmt) {
    header('Location: profile.php');
    exit;
}

$stmt->bind_param('i', $idea_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows !== 1) {
    $stmt->close();
    header('Location: profile.php');
    exit;
}

$row = $res->fetch_assoc();
$stmt->close();

$owner_id = (int)$row['user_id'];
$image_path = $row['image_path'];

// Permission rule: owner can delete their own idea whereas moderators can delete any idea
if ($user_id !== $owner_id && $user_level < 2) {
    header('Location: idea.php?id=' . $idea_id);
    exit;
}

// Delete the idea 
// If cascades aren't set, need to delete from likes/comments manually.
$stmtDel = $mysqli->prepare('DELETE FROM ideas WHERE id = ? LIMIT 1');
if ($stmtDel) {
    $stmtDel->bind_param('i', $idea_id);
    $stmtDel->execute();
    $stmtDel->close();
}

// To prevents deleting arbitrary server files via a crafted path.
if (!empty($image_path)) {
    $safe_prefix = 'uploads/';
    if (strpos($image_path, $safe_prefix) === 0 && file_exists($image_path)) {
        // suppresses warnings if unlink fails
        @unlink($image_path);
    }
}

// Return user to profile after delete
header('Location: profile.php');
exit;
