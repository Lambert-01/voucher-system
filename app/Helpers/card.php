<?php

function getCardTemplatePaths() {
    $publicTemplateDir = __DIR__ . '/../../public/assets/cards/templates/';
    $sourceTemplateDir = __DIR__ . '/../../voucher  card/';

    return [
        'front' => file_exists($publicTemplateDir . 'side1.png') ? $publicTemplateDir . 'side1.png' : $sourceTemplateDir . 'side1.png',
        'back' => file_exists($publicTemplateDir . 'side2.png') ? $publicTemplateDir . 'side2.png' : $sourceTemplateDir . 'side2.png'
    ];
}

function cardFontPath($bold = false) {
    $paths = $bold ? [
        __DIR__ . '/../../public/assets/fonts/arialbd.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/Library/Fonts/Arial Bold.ttf'
    ] : [
        __DIR__ . '/../../public/assets/fonts/arial.ttf',
        '/System/Library/Fonts/Supplemental/Arial.ttf',
        '/Library/Fonts/Arial.ttf'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    return null;
}

function cardTextBox($image, $text, $x, $y, $width, $size, $color, $font, $align = 'left') {
    $text = trim((string)$text);
    if ($text === '') {
        return;
    }

    if (!$font) {
        imagestring($image, 5, (int)$x, (int)$y, $text, $color);
        return;
    }

    while ($size > 10) {
        $box = imagettfbbox($size, 0, $font, $text);
        $textWidth = abs($box[2] - $box[0]);
        if ($textWidth <= $width) {
            break;
        }
        $size--;
    }

    $box = imagettfbbox($size, 0, $font, $text);
    $textWidth = abs($box[2] - $box[0]);
    $drawX = $x;
    if ($align === 'center') {
        $drawX = $x + (($width - $textWidth) / 2);
    } elseif ($align === 'right') {
        $drawX = $x + ($width - $textWidth);
    }

    imagettftext($image, $size, 0, (int)$drawX, (int)$y, $color, $font, $text);
}

function cardQrFilesystemPath($qrCodePath) {
    if (!$qrCodePath) {
        return null;
    }

    if (file_exists($qrCodePath)) {
        return $qrCodePath;
    }

    $publicPath = __DIR__ . '/../../public' . $qrCodePath;
    if (file_exists($publicPath)) {
        return $publicPath;
    }

    return null;
}

function generateVoucherCard($voucher, $templateFront, $templateBack, $outputPath) {
    $frontPath = $outputPath . '_front.png';
    $backPath = $outputPath . '_back.png';

    $front = imagecreatefrompng($templateFront);
    if (!$front) {
        return false;
    }

    imagealphablending($front, true);
    imagesavealpha($front, true);

    $w = imagesx($front);
    $h = imagesy($front);
    $green = imagecolorallocate($front, 12, 92, 22);
    $blue = imagecolorallocate($front, 20, 59, 135);
    $orange = imagecolorallocate($front, 241, 91, 18);
    $gold = imagecolorallocate($front, 184, 120, 8);
    $whiteOverlay = imagecolorallocatealpha($front, 255, 255, 255, 4);
    $boldFont = cardFontPath(true);
    $regularFont = cardFontPath(false);

    $amount = number_format((float)($voucher['original_amount'] ?? 0), 0) . ' RWF';
    $clientName = strtoupper((string)($voucher['client_name'] ?? ''));

    cardTextBox($front, $voucher['voucher_no'] ?? '', $w * 0.118, $h * 0.416, $w * 0.21, 21, $green, $boldFont);
    cardTextBox($front, $voucher['eva_id'] ?? 'N/A', $w * 0.426, $h * 0.416, $w * 0.185, 21, $blue, $boldFont);
    cardTextBox($front, $amount, $w * 0.728, $h * 0.416, $w * 0.195, 21, $orange, $boldFont);

    imagefilledrectangle($front, (int)($w * 0.185), (int)($h * 0.604), (int)($w * 0.85), (int)($h * 0.699), $whiteOverlay);
    cardTextBox($front, $clientName, $w * 0.185, $h * 0.674, $w * 0.665, 44, $gold, $regularFont ?: $boldFont, 'center');

    imagepng($front, $frontPath, 3);
    imagedestroy($front);

    $back = imagecreatefrompng($templateBack);
    if (!$back) {
        return false;
    }

    imagealphablending($back, true);
    imagesavealpha($back, true);

    $bw = imagesx($back);
    $bh = imagesy($back);
    $qrPath = cardQrFilesystemPath($voucher['qr_code_path'] ?? null);
    $white = imagecolorallocate($back, 255, 255, 255);
    imagefilledrectangle($back, (int)($bw * 0.7095), (int)($bh * 0.348), (int)($bw * 0.8965), (int)($bh * 0.651), $white);

    if ($qrPath) {
        $qr = imagecreatefrompng($qrPath);
        if ($qr) {
            imagecopyresampled(
                $back,
                $qr,
                (int)($bw * 0.7405),
                (int)($bh * 0.384),
                0,
                0,
                (int)($bw * 0.126),
                (int)($bw * 0.126),
                imagesx($qr),
                imagesy($qr)
            );
            imagedestroy($qr);
        }
    }

    $backBlue = imagecolorallocate($back, 15, 61, 133);
    cardTextBox($back, $voucher['voucher_no'] ?? '', $bw * 0.708, $bh * 0.648, $bw * 0.20, 16, $backBlue, $boldFont, 'center');

    imagepng($back, $backPath, 3);
    imagedestroy($back);

    return ['front' => $frontPath, 'back' => $backPath];
}

function bulkGenerateCards($vouchers) {
    $templates = getCardTemplatePaths();
    $outputDir = __DIR__ . '/../../public/assets/cards/';

    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $results = [];
    foreach ($vouchers as $voucher) {
        $outputPath = $outputDir . 'voucher_' . $voucher['id'];
        $cards = generateVoucherCard($voucher, $templates['front'], $templates['back'], $outputPath);
        if ($cards) {
            $results[] = $cards;
        }
    }

    return $results;
}
