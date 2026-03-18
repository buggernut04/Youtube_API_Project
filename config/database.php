<?php
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');
 
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
 
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $name = $_ENV['DB_NAME'] ?? 'youtube_manager_db';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
 
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
 
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
 
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Don't expose connection details to the browser
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed. Check server logs.']));
    }
 
    return $pdo;
}