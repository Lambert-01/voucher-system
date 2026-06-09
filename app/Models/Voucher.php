<?php
class Voucher {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO vouchers (batch_id, voucher_no, client_name, eva_id, original_amount, balance, status, qr_code_path, qr_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['batch_id'],
            $data['voucher_no'],
            $data['client_name'],
            $data['eva_id'] ?? null,
            $data['original_amount'],
            $data['balance'] ?? $data['original_amount'],
            $data['status'] ?? 'active',
            $data['qr_code_path'] ?? null,
            $data['qr_token'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function getAll() {
        $sql = "SELECT v.*, vb.batch_name, vb.batch_month, c.company_name 
                FROM vouchers v
                JOIN voucher_batches vb ON vb.id = v.batch_id
                JOIN companies c ON c.id = vb.company_id
                ORDER BY v.created_at DESC";
        return $this->pdo->query($sql)->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT v.*, vb.batch_name, vb.batch_month, c.company_name FROM vouchers v JOIN voucher_batches vb ON vb.id = v.batch_id JOIN companies c ON c.id = vb.company_id WHERE v.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByVoucherNo($voucherNo, $token = null) {
        $stmt = $this->pdo->prepare("SELECT v.*, vb.batch_name, vb.batch_month, c.company_name FROM vouchers v JOIN voucher_batches vb ON vb.id = v.batch_id JOIN companies c ON c.id = vb.company_id WHERE v.voucher_no = ?");
        $stmt->execute([$voucherNo]);
        $voucher = $stmt->fetch();
        
        if ($voucher && $token && $voucher['qr_token']) {
            if ($voucher['qr_token'] !== $token) {
                return false;
            }
        }
        
        return $voucher;
    }
    
    public function getByBatch($batchId) {
        $stmt = $this->pdo->prepare("SELECT * FROM vouchers WHERE batch_id = ? ORDER BY voucher_no ASC");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE vouchers SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function updateBalance($id, $balance, $status) {
        $stmt = $this->pdo->prepare("UPDATE vouchers SET balance = ?, status = ? WHERE id = ?");
        return $stmt->execute([$balance, $status, $id]);
    }
    
    public function redeem($voucherId, $amountUsed, $receiptNo, $cashierId, $notes = null) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("SELECT * FROM vouchers WHERE id = ? FOR UPDATE");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch();
            
            if (!$voucher) {
                throw new Exception("Voucher not found");
            }
            
            if (!in_array($voucher['status'], ['active', 'partially_used'])) {
                throw new Exception("Voucher is not usable. Status: " . $voucher['status']);
            }
            
            $previousBalance = (float)$voucher['balance'];
            
            if ($amountUsed <= 0) {
                throw new Exception("Amount must be greater than zero");
            }
            
            if ($amountUsed > $previousBalance) {
                throw new Exception("Insufficient voucher balance. Available: " . formatMoney($previousBalance));
            }
            
            $newBalance = $previousBalance - $amountUsed;
            $newStatus = $newBalance <= 0 ? 'used' : 'partially_used';
            
            $update = $this->pdo->prepare("UPDATE vouchers SET balance = ?, status = ? WHERE id = ?");
            $update->execute([$newBalance, $newStatus, $voucherId]);
            
            $insert = $this->pdo->prepare("INSERT INTO voucher_transactions (voucher_id, receipt_no, previous_balance, amount_used, new_balance, cashier_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$voucherId, $receiptNo, $previousBalance, $amountUsed, $newBalance, $cashierId, $notes]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'previous_balance' => $previousBalance,
                'amount_used' => $amountUsed,
                'new_balance' => $newBalance,
                'status' => $newStatus
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getTransactions($voucherId) {
        $stmt = $this->pdo->prepare("SELECT vt.*, u.full_name as cashier_name FROM voucher_transactions vt JOIN users u ON u.id = vt.cashier_id WHERE vt.voucher_id = ? ORDER BY vt.created_at DESC");
        $stmt->execute([$voucherId]);
        return $stmt->fetchAll();
    }
}
