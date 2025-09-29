<?php
// Database configuration settings

$host = '127.0.0.1'; // Database host (use IP to avoid socket/named-pipe issues)
$port = 3306; // MySQL port (default XAMPP port is 3306; adjust if different)
$dbname = 'social_platform'; // Database name
$username = 'root'; // Database username (XAMPP default)
$password = ''; // Database password (XAMPP default empty)
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5, // seconds
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Handle connection error
    die("Connection failed: " . $e->getMessage());
}
?>