<?php
require_once '../includes/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create pending registrations table
    $sql = "CREATE TABLE IF NOT EXISTS `pending_registrations` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `email` varchar(255) NOT NULL,
      `first_name` varchar(100) NOT NULL,
      `last_name` varchar(100) NOT NULL,
      `phone` varchar(20) DEFAULT NULL,
      `password_hash` varchar(255) NOT NULL,
      `agree_terms` tinyint(1) NOT NULL DEFAULT 1,
      `newsletter` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `expires_at` datetime NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`),
      KEY `expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "✅ Pending registrations table created successfully!\n";
    
    // Clean up old data
    $db->exec("DELETE FROM `pending_registrations` WHERE `expires_at` < NOW()");
    echo "✅ Cleaned up expired pending registrations\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
