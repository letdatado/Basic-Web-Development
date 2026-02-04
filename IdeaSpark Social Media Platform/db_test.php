<?php
require_once 'includes/db_connect.php';

echo '<!DOCTYPE html>';
echo '<html lang="en"><head><meta charset="UTF-8"><title>DB Test</title></head><body>';

$result = $mysqli->query('SELECT 1');

if ($result) {
    echo '<p>Database connection looks OK.</p>';
} else {
    echo '<p>Database query failed.</p>';
}

echo '</body></html>';
