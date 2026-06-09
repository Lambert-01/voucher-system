<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/qr.php';
require_once __DIR__ . '/../../app/Helpers/card_template.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$batchModel = new VoucherBatch($pdo);
$voucherModel = new Voucher($pdo);

$batchId = $_GET['id'] ?? 0;
$batch = $batchModel->getById($batchId);

if (!$batch) {
    die('Batch not found');
}

$vouchers = $voucherModel->getByBatch($batchId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Batch Cards - <?= htmlspecialchars($batch['batch_code']) ?></title>
    <style>
        @page { size: 167.2mm 94.1mm; margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #e5e7eb;
        }
        .voucher-card-page {
            width: 167.2mm;
            height: 94.1mm;
            page-break-after: always;
            position: relative;
        }
        .voucher-card {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
            background: #fff;
        }
        .voucher-card-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .card-field {
            position: absolute;
            z-index: 2;
            color: #07111f;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0;
        }
        .field-voucher-no {
            left: 12%;
            top: 39.5%;
            width: 19%;
            font-size: 14pt;
            color: #0c5c16;
        }
        .field-eva-id {
            left: 42.6%;
            top: 39.5%;
            width: 18%;
            font-size: 14pt;
            color: #143b87;
        }
        .field-amount {
            left: 72.6%;
            top: 39.5%;
            width: 20%;
            font-size: 14pt;
            color: #f15b12;
        }
        .field-client-line {
            left: 21.4%;
            top: 48.5%;
            width: 72%;
            font-size: 15pt;
            color: #07111f;
        }
        .field-customer-name {
            position: absolute;
            z-index: 3;
            left: 18.5%;
            top: 59.7%;
            width: 66.5%;
            height: 11%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5mm;
            background: rgba(255, 255, 255, 0.92);
            color: #b87808;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(18pt, 4vw, 36pt);
            font-weight: 500;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .field-company {
            position: absolute;
            z-index: 2;
            left: 39%;
            top: 78.1%;
            width: 22%;
            text-align: center;
            color: rgba(7, 95, 19, 0.68);
            font-size: 8pt;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .field-qr {
            position: absolute;
            z-index: 3;
            left: 75.65%;
            top: 38.55%;
            width: 9.9%;
            aspect-ratio: 1;
            object-fit: contain;
            background: white;
            padding: 0.8mm;
        }
        .field-back-voucher {
            position: absolute;
            z-index: 3;
            left: 70.8%;
            top: 63.6%;
            width: 20%;
            text-align: center;
            color: #0f3d85;
            font-size: 8pt;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .empty-state {
            margin: 32px auto;
            padding: 24px;
            width: min(520px, calc(100% - 40px));
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-align: center;
            color: #374151;
        }
        @media screen {
            body {
                min-height: 100vh;
                padding: 24px;
                display: flex;
                flex-wrap: wrap;
                gap: 24px;
                justify-content: center;
                align-items: flex-start;
            }
            .voucher-card-page {
                box-shadow: 0 16px 40px rgba(0,0,0,0.2);
                border-radius: 10px;
                overflow: hidden;
            }
        }
        @media print {
            body { background: white; }
            .voucher-card-page { box-shadow: none; }
            .empty-state { display: none; }
        }
    </style>
</head>
<body>
    <?php if (!$vouchers): ?>
        <div class="empty-state">No vouchers found in this batch.</div>
    <?php endif; ?>

    <?php foreach ($vouchers as $voucher): ?>
        <?php renderVoucherCardPages($voucher, $batch); ?>
    <?php endforeach; ?>

    <?php if ($vouchers): ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
    <?php endif; ?>
</body>
</html>
