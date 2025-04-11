<?php
// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
$host = 'localhost';
$dbname = 'event_management';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed']));
}
?>