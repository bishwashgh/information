<?php
/**
 * Root Config File - Redirects to includes/config.php
 * This file provides backward compatibility for files that expect config.php in the root
 */

require_once __DIR__ . '/includes/config.php';

// For backward compatibility, expose the PDO connection as $pdo
$pdo = Database::getInstance()->getConnection();
?>
