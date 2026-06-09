<?php
function formatMoney($amount) {
    return number_format($amount, 0, '.', ',') . ' RWF';
}

function parseAmount($input) {
    return floatval(str_replace(',', '', $input));
}
