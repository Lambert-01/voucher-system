<?php
session_start();

function checkSessionTimeout() {
    $lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 60) * 60;
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $lifetime) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout'] = true;
        return false;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) return false;
    return checkSessionTimeout();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('/login.php');
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        die('Access denied');
    }
}

function getUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ];
}

function logout() {
    session_destroy();
    redirectTo('/login.php');
}

function logAudit($pdo, $userId, $action, $description = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $description, $ip]);
}

if (!function_exists('showNotification')) {
    function showNotification($message, $type = 'info') {
        $_SESSION['notification'] = ['message' => $message, 'type' => $type];
    }
}

if (!function_exists('getNotification')) {
    function getNotification() {
        if (!isset($_SESSION['notification'])) {
            return null;
        }

        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
}

function basePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $publicPos = strpos($scriptName, '/public/');

    if ($publicPos !== false) {
        return substr($scriptName, 0, $publicPos + strlen('/public'));
    }

    return '';
}

function appUrl($path = '') {
    return rtrim(basePath(), '/') . '/' . ltrim($path, '/');
}

function redirectTo($path) {
    header('Location: ' . appUrl($path));
    exit;
}
