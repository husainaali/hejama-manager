<?php
// setup_db.php - Run this once to create the database tables
require_once 'includes/db_connect.php';

$sql = file_get_contents('database/schema.sql');

try {
    // Execute the schema
    $pdo->exec($sql);
    echo "<h1>Database Setup Successful!</h1>";
    echo "<p>Tables have been created. Please delete this file (setup_db.php) for security.</p>";
} catch (PDOException $e) {
    echo "<h1>Setup Failed</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
