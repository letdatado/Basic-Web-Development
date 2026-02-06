<?php
// Database conn settings
// These values are environment-specific
$host = '_host';
$db   = '_dbid';
$user = '_username';
$pass = '_password'; // MySQL password. HANDLE WITH CARE

// Create MySQLi conn
$mysqli = new mysqli($host, $user, $pass, $db);

// Fail fast if the conn cannot be established
if ($mysqli->connect_error) {
    // In production, log the error instead of displaying it
    die('Unable to connect to the database. Please try again later.');
}

// full Unicode support
$mysqli->set_charset('utf8mb4');
