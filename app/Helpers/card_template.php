<?php

function cardTemplateUrl($side) {
    return appUrl('/assets/cards/templates/' . $side . '.png');
}

function cardValue($row, $key, $fallback = '') {
    return htmlspecialchars((string)($row[$key] ?? $fallback));
}

function renderVoucherCardPages($voucher, $batch = null) {
    $companyName = $voucher['company_name'] ?? ($batch['company_name'] ?? '');
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
            <div class="card-field field-client-line"><?= cardValue($voucher, 'client_name') ?></div>
            <div class="field-customer-name"><?= cardValue($voucher, 'client_name') ?></div>
            <?php if ($companyName): ?>
                <div class="field-company"><?= htmlspecialchars($companyName) ?></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="voucher-card-page">
        <div class="voucher-card voucher-card-back">
            <img class="voucher-card-bg" src="<?= htmlspecialchars(cardTemplateUrl('side2')) ?>" alt="Voucher card back">
            <img class="field-qr" src="<?= htmlspecialchars(qrImageSource($voucher['voucher_no'], $voucher['qr_code_path'] ?? null)) ?>" alt="QR code">
            <div class="field-back-voucher"><?= cardValue($voucher, 'voucher_no') ?></div>
        </div>
    </section>
    <?php
}

