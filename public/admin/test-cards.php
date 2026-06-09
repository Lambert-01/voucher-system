<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/card.php';

requireRole('admin');

// Test data
$testVoucher = [
    'id' => 'test',
    'voucher_no' => 'HSV-2026-0001',
    'client_name' => 'JOHN DOE',
    'eva_id' => 'PX-Q3-2026-TEST',
    'original_amount' => 436800,
    'qr_code_path' => __DIR__ . '/../assets/qrcodes/sample_qr.png'
];

// Get templates
$templates = getCardTemplatePaths();

// Generate test card
$outputDir = __DIR__ . '/../assets/cards/';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$message = '';
if (isset($_GET['generate'])) {
    $outputPath = $outputDir . 'test_card';
    $result = generateVoucherCard($testVoucher, $templates['front'], $templates['back'], $outputPath);
    
    if ($result) {
        $message = '<div class="alert alert-success">Test card generated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to generate test card</div>';
    }
}

require_once __DIR__ . '/../../app/Views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Card Generation Test</h1>
            <p class="text-muted">Test the card generation system with sample data</p>
        </div>
    </div>

    <?= $message ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">System Check</h5>
                    
                    <table class="table table-sm">
                        <tr>
                            <td><strong>GD Extension:</strong></td>
                            <td>
                                <?php if (extension_loaded('gd')): ?>
                                    <span class="badge bg-success">✓ Installed</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Not Installed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Front Template:</strong></td>
                            <td>
                                <?php if (file_exists($templates['front'])): ?>
                                    <span class="badge bg-success">✓ Found</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Not Found</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Back Template:</strong></td>
                            <td>
                                <?php if (file_exists($templates['back'])): ?>
                                    <span class="badge bg-success">✓ Found</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Not Found</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Output Directory:</strong></td>
                            <td>
                                <?php if (is_writable($outputDir)): ?>
                                    <span class="badge bg-success">✓ Writable</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">⚠ Not Writable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <a href="?generate=1" class="btn btn-primary">
                        <i class="bi bi-play"></i> Generate Test Card
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Test Voucher Data</h5>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Voucher No:</strong></td>
                            <td><?= htmlspecialchars($testVoucher['voucher_no']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Client Name:</strong></td>
                            <td><?= htmlspecialchars($testVoucher['client_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>EVA ID:</strong></td>
                            <td><?= htmlspecialchars($testVoucher['eva_id']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Amount:</strong></td>
                            <td><?= number_format($testVoucher['original_amount']) ?> RWF</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['generate']) && file_exists($outputDir . 'test_card_front.png')): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Front Card (side1.png template)</h5>
                </div>
                <div class="card-body text-center">
                    <img src="/assets/cards/test_card_front.png?t=<?= time() ?>" 
                         class="img-fluid border" 
                         style="max-width: 100%;" 
                         alt="Front Card">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Back Card (side2.png template)</h5>
                </div>
                <div class="card-body text-center">
                    <img src="/assets/cards/test_card_back.png?t=<?= time() ?>" 
                         class="img-fluid border" 
                         style="max-width: 100%;" 
                         alt="Back Card">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col">
            <div class="card bg-light">
                <div class="card-body">
                    <h5>Template Information</h5>
                    <p><strong>Template Location:</strong> <code>/voucher card/</code></p>
                    <ul>
                        <li><strong>side1.png</strong> - Front card template</li>
                        <li><strong>side2.png</strong> - Back card template</li>
                    </ul>
                    <p class="mb-0"><small>The system overlays voucher data onto these templates. Adjust text positions in <code>/app/Helpers/card.php</code> if needed.</small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/Views/layouts/footer.php'; ?>
