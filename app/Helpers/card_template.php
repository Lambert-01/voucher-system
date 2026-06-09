<?php

function cardTemplateUrl($side) {
    return appUrl('/assets/cards/templates/' . $side . '.png');
}

function cardValue($row, $key, $fallback = '') {
    return htmlspecialchars((string)($row[$key] ?? $fallback));
}

function cardCustomerFontSize($name) {
    $length = strlen((string)$name);
    if ($length > 30) {
        return '18pt';
    }
    if ($length > 22) {
        return '21pt';
    }
    if ($length > 16) {
        return '24pt';
    }
    return '28pt';
}

function renderVoucherCardPages($voucher, $batch = null) {
    $amount = function_exists('formatMoney')
        ? formatMoney($voucher['original_amount'] ?? 0)
        : number_format((float)($voucher['original_amount'] ?? 0), 0) . ' RWF';
    ?>
    <section class="voucher-card-page">
        <div class="voucher-card voucher-card-front">
            <img class="voucher-card-bg" src="<?= htmlspecialchars(cardTemplateUrl('side1')) ?>" alt="Voucher card front">
            <div class="card-field field-voucher-no"><?= cardValue($voucher, 'voucher_no') ?></div>
            <div class="card-field field-eva-id"><?= cardValue($voucher, 'eva_id', 'N/A') ?></div>
            <div class="card-field field-amount"><?= htmlspecialchars($amount) ?></div>
            <div class="field-customer-name" style="font-size: <?= cardCustomerFontSize($voucher['client_name'] ?? '') ?>"><?= cardValue($voucher, 'client_name') ?></div>
        </div>
    </section>

    <section class="voucher-card-page">
        <div class="voucher-card voucher-card-back">
            <img class="voucher-card-bg" src="<?= htmlspecialchars(cardTemplateUrl('side2')) ?>" alt="Voucher card back">
            <div class="field-qr-cover"></div>
            <img class="field-qr" src="<?= htmlspecialchars(qrImageSource($voucher['voucher_no'], $voucher['qr_code_path'] ?? null)) ?>" alt="QR code">
            <div class="field-back-voucher"><?= cardValue($voucher, 'voucher_no') ?></div>
        </div>
    </section>
    <?php
}
