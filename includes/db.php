<?php
/**
 * Database Connection for Admin Panel
 * Provides PDO connection using the existing Database class
 */

// Include the main database configuration
require_once __DIR__ . '/../config/database.php';

// Get PDO connection from the Database singleton
try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    // Fallback connection if Database class fails
    $host = 'localhost';
    $dbname = 'if0_39725628_onlinestore';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
