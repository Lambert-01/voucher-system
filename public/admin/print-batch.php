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

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'cb_from' => trim($_GET['cb_from'] ?? ''),
    'cb_to' => trim($_GET['cb_to'] ?? ''),
];
$vouchers = $voucherModel->getByBatchFiltered($batchId, $filters);
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
        .field-line-cover {
            position: absolute;
            z-index: 1;
            background: white;
        }
        .cover-voucher-no-line {
            left: 11.8%;
            top: 40%;
            width: 21%;
            height: 4%;
        }
        .cover-eva-line {
            left: 42.6%;
            top: 40%;
            width: 19%;
            height: 4%;
        }
        .cover-amount-line {
            left: 72.8%;
            top: 40%;
            width: 22%;
            height: 4%;
        }
        .cover-client-line {
            left: 21.2%;
            top: 48.2%;
            width: 72.2%;
            height: 4.2%;
        }
        .field-voucher-no {
            left: 11.8%;
            top: 40.1%;
            width: 21%;
            font-size: 10.5pt;
            color: #0c5c16;
        }
        .field-eva-id {
            left: 42.6%;
            top: 40.1%;
            width: 18.5%;
            font-size: 10.5pt;
            color: #143b87;
        }
        .field-amount {
            left: 72.8%;
            top: 40.1%;
            width: 19.5%;
            font-size: 10.5pt;
            color: #f15b12;
        }
        .field-customer-name {
            position: absolute;
            z-index: 3;
            left: 18.5%;
            top: 60.4%;
            width: 66.5%;
            height: 9.5%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5mm;
            background: white;
            color: #b87808;
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 500;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .field-qr-cover {
            position: absolute;
            z-index: 2;
            left: 70.95%;
            top: 34.8%;
            width: 18.7%;
            height: 30.3%;
            background: white;
            border: 0.28mm dashed #16418d;
            border-radius: 3mm;
        }
        .field-qr {
            position: absolute;
            z-index: 3;
            left: 74.05%;
            top: 38.4%;
            width: 12.6%;
            aspect-ratio: 1;
            object-fit: contain;
            background: white;
            padding: 0.6mm;
        }
        .field-back-voucher {
            position: absolute;
            z-index: 3;
            left: 70.8%;
            top: 61.2%;
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
