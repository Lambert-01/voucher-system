<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('cashier');

$pdo = getDB();
$voucherModel = new Voucher($pdo);

$voucher = null;
$success = '';
$error = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'search') {
        $voucherNo = trim($_POST['voucher_no'] ?? '');
        if ($voucherNo) {
            $voucher = $voucherModel->getByVoucherNo($voucherNo);
            if (!$voucher) {
                $error = 'Voucher not found';
            }
        }
    } elseif ($action === 'redeem') {
        $voucherId = $_POST['voucher_id'];
        $amountUsed = parseAmount($_POST['amount_used']);
        $receiptNo = trim($_POST['receipt_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($receiptNo)) {
            $error = 'Receipt number is required';
            $voucher = $voucherModel->getById($voucherId);
        } else {
            $result = $voucherModel->redeem($voucherId, $amountUsed, $receiptNo, $_SESSION['user_id'], $notes);
            
            if ($result['success']) {
                $success = 'Voucher redeemed successfully';
                logAudit($pdo, $_SESSION['user_id'], 'voucher_redeem', "Redeemed voucher ID $voucherId for " . formatMoney($amountUsed));
                $voucher = $voucherModel->getById($voucherId);
            } else {
                $error = $result['message'];
                $voucher = $voucherModel->getById($voucherId);
            }
        }
    }
}

$title = 'Scan Voucher';
ob_start();
?>

<div class="scan-container">
<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-qrcode"></i> Scan Voucher</h1>
        <p>Scan QR code or enter voucher number manually</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card scan-input-card">
    <h2><i class="fas fa-search"></i> Enter Voucher Number</h2>
    <p style="color: var(--gray-700); margin-bottom: 20px;">Scan the QR code or manually enter the voucher number</p>
    <form method="POST" class="no-validation">
        <input type="hidden" name="action" value="search">
        <div class="form-group">
            <input type="text" name="voucher_no" id="voucher_no" placeholder="HSV-2024-0001" autofocus required 
                   style="font-size: 1.25rem; text-align: center; padding: 18px; border: 2px solid var(--brand-green); letter-spacing: 1px; text-transform: uppercase;">
            <small><i class="fas fa-info-circle"></i> QR codes will auto-submit after scanning</small>
        </div>
        <button type="submit" class="btn btn-primary btn-large btn-block"><i class="fas fa-search"></i> Search Voucher</button>
    </form>
</div>

<?php if ($voucher): ?>
<div class="card voucher-details">
    <h2><i class="fas fa-ticket-alt"></i> Voucher Details</h2>
    
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <div>
                <div style="font-size: 0.813rem; color: var(--gray-700); margin-bottom: 4px;">VOUCHER NUMBER</div>
                <div style="font-size: 1.125rem; font-weight: 700; color: var(--charcoal);"><?= htmlspecialchars($voucher['voucher_no']) ?></div>
            </div>
            <div>
                <div style="font-size: 0.813rem; color: var(--gray-700); margin-bottom: 4px;">STATUS</div>
                <div><span class="badge badge-<?= $voucher['status'] ?>" style="font-size: 0.875rem; padding: 6px 12px;"><?= strtoupper($voucher['status']) ?></span></div>
            </div>
        </div>
        
        <div style="background: var(--gray-50); padding: 16px; border-radius: 6px; margin-bottom: 16px;">
            <div style="font-size: 0.938rem; color: var(--gray-700); margin-bottom: 4px;">CLIENT NAME</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--charcoal);"><?= htmlspecialchars($voucher['client_name']) ?></div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <div style="font-size: 0.813rem; color: var(--gray-700); margin-bottom: 4px;">EVA ID</div>
                <div style="font-weight: 600;"><?= htmlspecialchars($voucher['eva_id'] ?? 'N/A') ?></div>
            </div>
            <div>
                <div style="font-size: 0.813rem; color: var(--gray-700); margin-bottom: 4px;">COMPANY</div>
                <div style="font-weight: 600;"><?= htmlspecialchars($voucher['company_name']) ?></div>
            </div>
        </div>
        
        <div style="background: #f0fdf4; border: 2px solid var(--success); padding: 20px; border-radius: 8px; text-align: center;">
            <div style="font-size: 0.875rem; color: var(--gray-700); margin-bottom: 8px;">CURRENT BALANCE</div>
            <div style="font-size: 3rem; font-weight: 700; color: var(--success); line-height: 1;"><?= formatMoney($voucher['balance']) ?></div>
            <div style="font-size: 0.875rem; color: var(--gray-700); margin-top: 8px;">Original: <?= formatMoney($voucher['original_amount']) ?></div>
        </div>
    </div>
    
    <?php if (in_array($voucher['status'], ['active', 'partially_used']) && $voucher['balance'] > 0): ?>
    <div class="redeem-section">
        <h3><i class="fas fa-cash-register"></i> Redeem Voucher</h3>
        <form method="POST">
            <input type="hidden" name="action" value="redeem">
            <input type="hidden" name="voucher_id" value="<?= $voucher['id'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-money-bill-wave"></i> Amount Used *</label>
                    <input type="number" name="amount_used" step="0.01" max="<?= $voucher['balance'] ?>" required style="font-size: 1.125rem; font-weight: 600;">
                    <small>Maximum available: <?= formatMoney($voucher['balance']) ?></small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-receipt"></i> Receipt Number *</label>
                    <input type="text" name="receipt_no" placeholder="POS receipt number" required>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Notes (Optional)</label>
                <textarea name="notes" rows="2" placeholder="Optional notes about this transaction"></textarea>
            </div>
            
            <button type="submit" class="btn btn-success btn-large btn-block"><i class="fas fa-check-circle"></i> Redeem Voucher Now</button>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-warning" style="font-size: 1rem;">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>This voucher cannot be redeemed</strong><br>
            Status: <strong><?= strtoupper($voucher['status']) ?></strong>
            <?php if ($voucher['balance'] <= 0): ?>
            <br>Balance is zero.
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($result && $result['success']): ?>
    <div class="transaction-result">
        <h3><i class="fas fa-check-double"></i> Transaction Completed</h3>
        <div style="background: white; padding: 16px; border-radius: 6px; margin-top: 12px;">
            <table class="details-table">
                <tr>
                    <th>Previous Balance:</th>
                    <td style="font-size: 1.125rem; font-weight: 600;"><?= formatMoney($result['previous_balance']) ?></td>
                </tr>
                <tr>
                    <th>Amount Used:</th>
                    <td style="font-size: 1.125rem; font-weight: 600; color: var(--danger);"><?= formatMoney($result['amount_used']) ?></td>
                </tr>
                <tr>
                    <th>New Balance:</th>
                    <td style="font-size: 1.5rem; font-weight: 700; color: var(--success);"><?= formatMoney($result['new_balance']) ?></td>
                </tr>
                <tr>
                    <th>New Status:</th>
                    <td><span class="badge badge-<?= $result['status'] ?>" style="font-size: 0.875rem;"><?= $result['status'] ?></span></td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
</div>

<script>
document.getElementById('voucher_no').focus();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
