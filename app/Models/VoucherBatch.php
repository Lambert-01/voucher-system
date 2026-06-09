<?php
class VoucherBatch {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO voucher_batches (company_id, batch_code, batch_name, batch_month, total_vouchers, total_amount, payment_status, payment_reference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['company_id'],
            $data['batch_code'],
            $data['batch_name'],
            $data['batch_month'],
            $data['total_vouchers'] ?? 0,
            $data['total_amount'] ?? 0,
            $data['payment_status'] ?? 'pending',
            $data['payment_reference'] ?? null,
            $data['notes'] ?? null,
            $data['created_by']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function generateCode($companyName, $batchMonth) {
        $prefix = $this->companyPrefix($companyName);
        $baseCode = $prefix . '-' . date('Y-m', strtotime($batchMonth . '-01'));
        $code = $baseCode;
        $counter = 2;

        while ($this->codeExists($code)) {
            $code = $baseCode . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $code;
    }

    public function generateName($companyName, $batchMonth) {
        $prefix = $this->companyPrefix($companyName);
        return $prefix . ' ' . date('F Y', strtotime($batchMonth . '-01')) . ' Vouchers';
    }

    private function companyPrefix($companyName) {
        if (preg_match('/\(([^)]+)\)/', $companyName, $matches)) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $matches[1]));
        }

        $words = preg_split('/\s+/', trim($companyName));
        $prefix = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $prefix .= strtoupper($word[0]);
            }
        }

        return substr($prefix ?: 'BATCH', 0, 8);
    }

    private function codeExists($code) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM voucher_batches WHERE batch_code = ?");
        $stmt->execute([$code]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE voucher_batches SET company_id = ?, batch_name = ?, batch_month = ?, payment_status = ?, payment_reference = ?, notes = ? WHERE id = ?");
        return $stmt->execute([
            $data['company_id'],
            $data['batch_name'],
            $data['batch_month'],
            $data['payment_status'],
            $data['payment_reference'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }
    
    public function getAll() {
        $sql = "SELECT vb.*, c.company_name, u.full_name as creator_name 
                FROM voucher_batches vb
                JOIN companies c ON c.id = vb.company_id
                JOIN users u ON u.id = vb.created_by
                ORDER BY vb.created_at DESC";
        return $this->pdo->query($sql)->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT vb.*, c.company_name FROM voucher_batches vb JOIN companies c ON c.id = vb.company_id WHERE vb.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByCompany($companyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM voucher_batches WHERE company_id = ? ORDER BY created_at DESC");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }
    
    public function updateTotals($batchId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total_vouchers, SUM(original_amount) as total_amount FROM vouchers WHERE batch_id = ?");
        $stmt->execute([$batchId]);
        $result = $stmt->fetch();
        
        $update = $this->pdo->prepare("UPDATE voucher_batches SET total_vouchers = ?, total_amount = ? WHERE id = ?");
        $update->execute([$result['total_vouchers'], $result['total_amount'] ?? 0, $batchId]);
    }
}
