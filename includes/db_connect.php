<?php
// db_connect.php - Database connection using PDO

// Hostinger Database Credentials (User should replace these)
$host = 'localhost'; 
$db   = 'u640030385_alsayyida'; 
$user = 'u640030385_alsayyida';   
$pass = 'H@952026h';  
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // For security, in production you might want to log this instead of echo
     die("Database connection failed: " . $e->getMessage());
}
?>
