<?php
require_once __DIR__ . '/../config/database.php';

class Medicine {
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['db'];
    }
    
    public function ensureAllBatchesExist() {
        $this->db->query("
            INSERT INTO medicine_prices (medicine_id, batch_no, expiry_date, purchase_price, selling_price, quantity)
            SELECT m.id, CONCAT('BATCH', LPAD(m.id, 4, '0'), DATE_FORMAT(CURDATE(), '%y%m')), 
                   DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0, 0, 0
            FROM medicines m
            WHERE m.is_active = 1 
            AND NOT EXISTS (SELECT 1 FROM medicine_prices mp WHERE mp.medicine_id = m.id)
        ");
    }
    
    public function getAll($search = '', $limit = 100) {
        $this->ensureAllBatchesExist();
        
        $sql = "SELECT m.*, b.name as brand_name, c.name as category_name,
                COALESCE((SELECT SUM(mp.quantity) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as total_stock,
                COALESCE((SELECT MAX(mp.selling_price) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as selling_price,
                COALESCE((SELECT MAX(mp.purchase_price) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as purchase_price,
                (SELECT mp.batch_no FROM medicine_prices mp WHERE mp.medicine_id = m.id AND mp.quantity > 0 ORDER BY mp.expiry_date ASC LIMIT 1) as batch_no
                FROM medicines m
                LEFT JOIN brands b ON m.brand_id = b.id
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.is_active = 1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (m.name LIKE ? OR m.salt_formula LIKE ? OR b.name LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        $sql .= " ORDER BY m.name ASC LIMIT " . intval($limit);
        
        return $this->db->select($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT m.*, b.name as brand_name, c.name as category_name
             FROM medicines m
             LEFT JOIN brands b ON m.brand_id = b.id
             LEFT JOIN categories c ON m.category_id = c.id
             WHERE m.id = ?",
            [$id]
        );
    }
    
    public function search($term, $limit = 20) {
        $sql = "SELECT m.*, b.name as brand_name, c.name as category_name,
                COALESCE((SELECT SUM(mp.quantity) FROM medicine_prices mp WHERE mp.medicine_id = m.id AND mp.expiry_date >= CURDATE()), 0) as total_stock,
                COALESCE((SELECT MAX(mp.selling_price) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as selling_price
                FROM medicines m
                LEFT JOIN brands b ON m.brand_id = b.id
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.is_active = 1";
        $params = [];
        
        if ($term !== '') {
            $sql .= " AND (m.name LIKE ? OR m.salt_formula LIKE ? OR b.name LIKE ?)";
            $params = ["%$term%", "%$term%", "%$term%"];
        }
        
        $sql .= " ORDER BY m.name ASC LIMIT " . intval($limit);
        
        return $this->db->select($sql, $params);
    }
    
    public function getBySalt($salt) {
        return $this->db->select(
            "SELECT m.*, b.name as brand_name 
             FROM medicines m
             LEFT JOIN brands b ON m.brand_id = b.id
             WHERE m.salt_formula LIKE ? AND m.is_active = 1",
            ["%$salt%"]
        );
    }
    
    public function getLowStock() {
        return $this->db->select(
            "SELECT m.id, m.name, m.min_stock_level, m.is_active,
                    COALESCE(SUM(mp.quantity), 0) as total_stock,
                    b.name as brand_name
             FROM medicines m
             LEFT JOIN brands b ON m.brand_id = b.id
             LEFT JOIN medicine_prices mp ON m.id = mp.medicine_id
             WHERE m.is_active = 1
             GROUP BY m.id
             ORDER BY total_stock ASC"
        );
    }
    
    public function getExpiringSoon($days = 90) {
        return $this->db->select(
            "SELECT m.name, mp.batch_no, mp.expiry_date, mp.quantity, b.name as brand_name
             FROM medicine_prices mp
             JOIN medicines m ON mp.medicine_id = m.id
             LEFT JOIN brands b ON m.brand_id = b.id
             WHERE mp.quantity > 0
             ORDER BY mp.expiry_date ASC
             LIMIT 20"
        );
    }
    
    public function create($data) {
        return $this->db->insert('medicines', $data);
    }
    
    public function update($id, $data) {
        return $this->db->update('medicines', $data, 'id = ?', [$id]);
    }
    
    public function updateDiscount($id, $discountType, $discountValue, $discountEnabled) {
        return $this->db->update('medicines', [
            'discount_type' => $discountType ?: null,
            'discount_value' => $discountValue ?: 0,
            'discount_enabled' => $discountEnabled ? 1 : 0
        ], 'id = ?', [$id]);
    }
    
    public function togglePause($id) {
        $medicine = $this->getById($id);
        if (!$medicine) return false;
        $newStatus = $medicine['is_paused'] ? 0 : 1;
        return $this->db->update('medicines', ['is_paused' => $newStatus], 'id = ?', [$id]);
    }
    
    public function getActiveMedicines($search = '', $limit = 100) {
        $this->ensureAllBatchesExist();
        
        // Check if is_paused column exists
        $hasPauseColumn = !empty($this->db->select("SHOW COLUMNS FROM medicines LIKE 'is_paused'"));
        
        $sql = "SELECT m.*, b.name as brand_name, c.name as category_name,
                COALESCE((SELECT SUM(mp.quantity) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as total_stock,
                COALESCE((SELECT MAX(mp.selling_price) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as selling_price,
                COALESCE((SELECT MAX(mp.purchase_price) FROM medicine_prices mp WHERE mp.medicine_id = m.id), 0) as purchase_price,
                (SELECT mp.batch_no FROM medicine_prices mp WHERE mp.medicine_id = m.id AND mp.quantity > 0 ORDER BY mp.expiry_date ASC LIMIT 1) as batch_no
                FROM medicines m
                LEFT JOIN brands b ON m.brand_id = b.id
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.is_active = 1" . ($hasPauseColumn ? " AND m.is_paused = 0" : "");
        $params = [];
        
        if ($search) {
            $sql .= " AND (m.name LIKE ? OR m.salt_formula LIKE ? OR b.name LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        $sql .= " ORDER BY m.name ASC LIMIT " . intval($limit);
        
        return $this->db->select($sql, $params);
    }
    
    public function calculateDiscount($medicineId, $quantity, $rate) {
        $medicine = $this->getById($medicineId);
        if (!$medicine || !$medicine['discount_enabled'] || !$medicine['discount_value']) {
            return 0;
        }
        
        $subtotal = $quantity * $rate;
        
        if ($medicine['discount_type'] === 'percentage') {
            return ($subtotal * $medicine['discount_value']) / 100;
        } else {
            // Fixed discount per unit
            return $medicine['discount_value'] * $quantity;
        }
    }
    
    public function delete($id) {
        return $this->db->update('medicines', ['is_active' => 0], 'id = ?', [$id]);
    }
    
    public function addPrice($data) {
        if (empty($data['batch_no'])) {
            $medicineId = $data['medicine_id'];
            $lastBatch = $this->db->selectOne(
                "SELECT batch_no FROM medicine_prices WHERE medicine_id = ? ORDER BY id DESC LIMIT 1",
                [$medicineId]
            );
            
            if ($lastBatch && preg_match('/(\d+)$/', $lastBatch['batch_no'], $matches)) {
                $num = intval($matches[1]) + 1;
                $data['batch_no'] = preg_replace('/\d+$/', str_pad($num, 4, '0', STR_PAD_LEFT), $lastBatch['batch_no']);
            } else {
                $data['batch_no'] = 'BATCH' . str_pad($medicineId, 4, '0', STR_PAD_LEFT) . date('ym');
            }
        }
        
        if (empty($data['expiry_date'])) {
            $data['expiry_date'] = date('Y-m-d', strtotime('+1 year'));
        }
        
        return $this->db->insert('medicine_prices', $data);
    }
    
    public function updateStock($priceId, $quantity) {
        return $this->db->query(
            "UPDATE medicine_prices SET quantity = quantity + ? WHERE id = ?",
            [$quantity, $priceId]
        );
    }
}
