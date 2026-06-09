<?php
class Company {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO companies (company_name, contact_person, phone, email, address, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['company_name'],
            $data['contact_person'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active'
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE companies SET company_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, status = ? WHERE id = ?");
        return $stmt->execute([
            $data['company_name'],
            $data['contact_person'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }
    
    public function getAll() {
        return $this->pdo->query("SELECT * FROM companies ORDER BY company_name ASC")->fetchAll();
    }
    
    public function getActive() {
        return $this->pdo->query("SELECT * FROM companies WHERE status = 'active' ORDER BY company_name ASC")->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM companies WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
