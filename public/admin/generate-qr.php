<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/qr.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$voucherModel = new Voucher($pdo);

$voucherId = $_GET['id'] ?? 0;
$voucher = $voucherModel->getById($voucherId);

if (!$voucher) {
    die('Voucher not found');
}

// Generate QR code
$qrPath = __DIR__ . '/../assets/qrcodes/' . $voucher['voucher_no'] . '.png';
$success = generateSimpleQRCode($voucher['voucher_no'], $qrPath);

if ($success) {
    $pdo->prepare("UPDATE vouchers SET qr_code_path = ? WHERE id = ?")->execute([
        '/assets/qrcodes/' . $voucher['voucher_no'] . '.png',
        $voucherId
    ]);
    
    logAudit($pdo, $_SESSION['user_id'], 'qr_generate', 'Generated QR for voucher: ' . $voucher['voucher_no']);
    
    redirectTo('/admin/voucher-view.php?id=' . $voucherId . '&success=QR+code+generated');
} else {
    redirectTo('/admin/voucher-view.php?id=' . $voucherId . '&error=Failed+to+generate+QR+code');
}
exit;
