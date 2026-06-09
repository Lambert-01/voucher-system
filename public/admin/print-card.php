<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/qr.php';
require_once __DIR__ . '/../../app/Helpers/card_template.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$voucherModel = new Voucher($pdo);

$voucherId = $_GET['id'] ?? 0;
$voucher = $voucherModel->getById($voucherId);

if (!$voucher) {
    die('Voucher not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Card - <?= htmlspecialchars($voucher['voucher_no']) ?></title>
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
        }
    </style>
</head>
<body>
    <?php renderVoucherCardPages($voucher); ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
