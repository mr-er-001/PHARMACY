<?php
require_once __DIR__ . '/../config/database.php';

class Vendor {
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['db'];
    }
    
    public function getAll($search = '') {
        $sql = "SELECT * FROM vendors WHERE is_active = 1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (name LIKE ? OR company_name LIKE ? OR phone LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        $sql .= " ORDER BY name ASC";
        return $this->db->select($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->selectOne("SELECT * FROM vendors WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        $vendorId = $this->db->insert('vendors', $data);
        
        if ($vendorId && $data['opening_balance'] > 0) {
            $this->db->insert('vendor_ledger', [
                'vendor_id' => $vendorId,
                'date' => date('Y-m-d'),
                'transaction_type' => 'opening',
                'credit' => $data['opening_balance'],
                'balance' => $data['opening_balance']
            ]);
        }
        
        return $vendorId;
    }
    
    public function update($id, $data) {
        return $this->db->update('vendors', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->db->update('vendors', ['is_active' => 0], 'id = ?', [$id]);
    }
    
    public function getBalance($vendorId) {
        $row = $this->db->selectOne(
            "SELECT balance FROM vendor_ledger WHERE vendor_id = ? ORDER BY id DESC LIMIT 1",
            [$vendorId]
        );
        return $row ? $row['balance'] : 0;
    }
    
    public function getLedger($vendorId) {
        return $this->db->select(
            "SELECT vl.*, vp.payment_method, vp.transaction_id, b.name as bank_name
             FROM vendor_ledger vl
             LEFT JOIN vendor_payments vp ON vl.reference_id = vp.id AND vl.transaction_type = 'payment'
             LEFT JOIN banks b ON vp.bank_id = b.id
             WHERE vl.vendor_id = ? ORDER BY vl.date DESC, vl.id DESC",
            [$vendorId]
        );
    }
    
    public function addPurchaseEntry($vendorId, $invoiceId, $amount) {
        $balance = $this->getBalance($vendorId);
        $newBalance = $balance + $amount;
        
        return $this->db->insert('vendor_ledger', [
            'vendor_id' => $vendorId,
            'date' => date('Y-m-d'),
            'transaction_type' => 'purchase',
            'reference_id' => $invoiceId,
            'debit' => $amount,
            'balance' => $newBalance
        ]);
    }
    
    public function addPaymentEntry($vendorId, $paymentId, $amount) {
        $balance = $this->getBalance($vendorId);
        $newBalance = $balance - $amount;
        
        return $this->db->insert('vendor_ledger', [
            'vendor_id' => $vendorId,
            'date' => date('Y-m-d'),
            'transaction_type' => 'payment',
            'reference_id' => $paymentId,
            'credit' => $amount,
            'balance' => $newBalance
        ]);
    }
}
