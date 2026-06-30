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
        $baseCode = $prefix . '-' . $this->periodCode($batchMonth);
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
        return $prefix . ' ' . $this->periodLabel($batchMonth) . ' Vouchers';
    }

    public function buildPeriodValue($periodType, $startDate, $endDate = null) {
        $start = DateTime::createFromFormat('Y-m-d', $startDate);
        if (!$start || $start->format('Y-m-d') !== $startDate) {
            throw new Exception('Please select a valid start date.');
        }

        switch ($periodType) {
            case 'daily':
                return $start->format('Y-m-d');

            case 'weekly':
                $weekEnd = clone $start;
                $weekEnd->modify('+6 days');
                return $start->format('Ymd') . '-' . $weekEnd->format('Ymd');

            case 'monthly':
                $monthEnd = clone $start;
                $monthEnd->modify('last day of this month');
                return $start->format('Ymd') . '-' . $monthEnd->format('Ymd');

            case 'custom':
                $end = DateTime::createFromFormat('Y-m-d', (string)$endDate);
                if (!$end || $end->format('Y-m-d') !== $endDate) {
                    throw new Exception('Please select a valid ending date.');
                }
                if ($end < $start) {
                    throw new Exception('Ending date cannot be before starting date.');
                }
                return $start->format('Ymd') . '-' . $end->format('Ymd');
        }

        throw new Exception('Please select a valid batch period.');
    }

    public function periodLabel($periodValue) {
        if (preg_match('/^\d{4}-\d{2}$/', $periodValue)) {
            return date('F Y', strtotime($periodValue . '-01'));
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodValue)) {
            return date('d M Y', strtotime($periodValue));
        }

        if (preg_match('/^(\d{4})-W(\d{2})$/', $periodValue, $matches)) {
            $start = new DateTime();
            $start->setISODate((int)$matches[1], (int)$matches[2]);
            $end = clone $start;
            $end->modify('+6 days');
            return 'Week ' . $matches[2] . ' (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})_to_(\d{4}-\d{2}-\d{2})$/', $periodValue, $matches)) {
            return date('d M Y', strtotime($matches[1])) . ' to ' . date('d M Y', strtotime($matches[2]));
        }

        if (preg_match('/^(\d{8})-(\d{8})$/', $periodValue, $matches)) {
            $start = DateTime::createFromFormat('Ymd', $matches[1]);
            $end = DateTime::createFromFormat('Ymd', $matches[2]);
            if ($start && $end) {
                return $start->format('d M Y') . ' to ' . $end->format('d M Y');
            }
        }

        return $periodValue;
    }

    private function periodCode($periodValue) {
        return strtoupper(str_replace(['_to_', ' ', '/'], ['-TO-', '-', '-'], $periodValue));
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
        $stmt = $this->pdo->prepare("UPDATE voucher_batches SET company_id = ?, batch_code = ?, batch_name = ?, batch_month = ?, payment_status = ?, payment_reference = ?, notes = ? WHERE id = ?");
        return $stmt->execute([
            $data['company_id'],
            $data['batch_code'],
            $data['batch_name'],
            $data['batch_month'],
            $data['payment_status'],
            $data['payment_reference'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }

    public function voucherCount($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE batch_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM voucher_batches WHERE id = ?");
        return $stmt->execute([$id]);
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
