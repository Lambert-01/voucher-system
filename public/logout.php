<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/auth.php';

if (isLoggedIn()) {
    $pdo = getDB();
    logAudit($pdo, $_SESSION['user_id'], 'logout', 'User logged out');
}

logout();
