<?php

function statusBadgeClass($status) {
    $classes = [
        'active' => 'badge-active',
        'partially_used' => 'badge-partially_used',
        'used' => 'badge-used',
        'blocked' => 'badge-blocked',
        'expired' => 'badge-expired',
        'cancelled' => 'badge-cancelled',
        'pending' => 'badge-pending',
        'paid' => 'badge-paid',
        'inactive' => 'badge-inactive'
    ];
    
    return $classes[$status] ?? 'badge-secondary';
}

function statusLabel($status) {
    $labels = [
        'active' => 'Active',
        'partially_used' => 'Partial',
        'used' => 'Used',
        'blocked' => 'Blocked',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        'pending' => 'Pending',
        'paid' => 'Paid',
        'inactive' => 'Inactive'
    ];
    
    return $labels[$status] ?? ucfirst($status);
}

function isCurrentPage($path) {
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($currentPath, $path) !== false;
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'M d, Y H:i') {
    return date($format, strtotime($datetime));
}

function truncate($text, $length = 50) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function sendCsvDownload($filename, array $headers, array $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function breadcrumb($items) {
    $html = '<nav class="breadcrumbs">';
    $count = count($items);
    foreach ($items as $index => $item) {
        if ($index < $count - 1) {
            $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a>';
            $html .= '<i class="fas fa-chevron-right" style="font-size: 0.75rem; color: var(--gray-400);"></i>';
        } else {
            $html .= '<span>' . htmlspecialchars($item['title']) . '</span>';
        }
    }
    $html .= '</nav>';
    return $html;
}

if (!function_exists('showNotification')) {
    function showNotification($message, $type = 'info') {
        $_SESSION['notification'] = ['message' => $message, 'type' => $type];
    }
}

if (!function_exists('getNotification')) {
    function getNotification() {
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            unset($_SESSION['notification']);
            return $notification;
        }
        return null;
    }
}
