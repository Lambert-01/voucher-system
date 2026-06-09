<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/card.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$voucherModel = new Voucher($pdo);

$voucherId = $_GET['id'] ?? 0;
$voucher = $voucherModel->getById($voucherId);

if (!$voucher) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Voucher not found']);
    exit;
}

// Get template paths
$templates = getCardTemplatePaths();

// Generate card images
$outputDir = __DIR__ . '/../assets/cards/';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$outputPath = $outputDir . 'voucher_' . $voucher['id'];
$cardImages = generateVoucherCard($voucher, $templates['front'], $templates['back'], $outputPath);

if ($cardImages) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'front' => appUrl('/assets/cards/voucher_' . $voucher['id'] . '_front.png'),
        'back' => appUrl('/assets/cards/voucher_' . $voucher['id'] . '_back.png')
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to generate card']);
}
