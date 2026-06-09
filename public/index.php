<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/auth.php';

if (!isLoggedIn()) {
    redirectTo('/login.php');
}

if ($_SESSION['role'] === 'boss') {
    redirectTo('/boss/dashboard.php');
} elseif ($_SESSION['role'] === 'admin') {
    redirectTo('/admin/dashboard.php');
} else {
    redirectTo('/cashier/scan.php');
}
