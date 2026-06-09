<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function generateQRCode($voucherNo, $savePath) {
    try {
        $qrCode = new QrCode($voucherNo);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $result->saveToFile($savePath);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function generateSimpleQRCode($voucherNo, $savePath) {
    $size = 300;
    $dir = dirname($savePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($voucherNo);
    $qrData = file_get_contents($url);
    if ($qrData) {
        file_put_contents($savePath, $qrData);
        return true;
    }
    return false;
}

function qrImageSource($voucherNo, $qrCodePath = null) {
    if ($qrCodePath) {
        $publicPath = dirname(__DIR__, 2) . '/public' . $qrCodePath;
        if (file_exists($publicPath) && function_exists('appUrl')) {
            return appUrl($qrCodePath);
        }
    }

    return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($voucherNo);
}
