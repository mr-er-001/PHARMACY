<?php
require_once __DIR__ . '/../config/database.php';

class Sale {
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['db'];
    }
    
    public function getAll($fromDate = '', $toDate = '', $saleType = '') {
        $sql = "SELECT s.*, u.name as created_by_name
                FROM sales s
                LEFT JOIN users u ON s.created_by = u.id
                WHERE 1=1";
        
        if ($fromDate) {
            $sql .= " AND s.sale_date >= '" . $this->db->escape($fromDate) . "'";
        }
        if ($toDate) {
            $sql .= " AND s.sale_date <= '" . $this->db->escape($toDate) . "'";
        }
        if ($saleType) {
            $sql .= " AND s.sale_type = '" . $this->db->escape($saleType) . "'";
        }
        
        $sql .= " ORDER BY s.id DESC";
        return $this->db->select($sql);
    }
    
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT s.*, u.name as created_by_name
             FROM sales s
             LEFT JOIN users u ON s.created_by = u.id
             WHERE s.id = ?",
            [$id]
        );
    }
    
    public function getItems($saleId) {
        return $this->db->select(
            "SELECT si.*, m.name as medicine_name, b.name as brand_name
             FROM sale_items si
             JOIN medicines m ON si.medicine_id = m.id
             LEFT JOIN brands b ON m.brand_id = b.id
             WHERE si.sale_id = ?",
            [$saleId]
        );
    }
    
    public function getItemsWithBatch($saleId) {
        return $this->db->select(
            "SELECT si.*, m.name as medicine_name, b.name as brand_name, mp.batch_no as batch_number
             FROM sale_items si
             JOIN medicines m ON si.medicine_id = m.id
             LEFT JOIN brands b ON m.brand_id = b.id
             LEFT JOIN medicine_prices mp ON si.medicine_id = mp.medicine_id AND si.batch_no = mp.batch_no
             WHERE si.sale_id = ?",
            [$saleId]
        );
    }
    
    public function create($data, $items, $paymentDetails = []) {
        $this->db->beginTransaction();
        
        try {
            $invoiceNo = $this->generateInvoiceNo();
            $data['invoice_no'] = $invoiceNo;
            
            // Add payment details if provided
            if (!empty($paymentDetails['bank_name'])) {
                $data['bank_name'] = sanitize($paymentDetails['bank_name']);
            }
            if (!empty($paymentDetails['card_details'])) {
                $data['card_details'] = sanitize($paymentDetails['card_details']);
            }
            if (!empty($paymentDetails['transaction_id'])) {
                $data['transaction_id'] = sanitize($paymentDetails['transaction_id']);
            }
            if (!empty($paymentDetails['payment_platform'])) {
                $data['payment_platform'] = sanitize($paymentDetails['payment_platform']);
            }
            
            $saleId = $this->db->insert('sales', $data);
            
            if (!$saleId) {
                throw new Exception("Failed to create sale");
            }
            
            $total = 0;
            foreach ($items as $item) {
                $itemData = [
                    'sale_id' => $saleId,
                    'medicine_id' => $item['medicine_id'],
                    'batch_no' => $item['batch_no'] ?? '',
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total' => $item['quantity'] * $item['rate']
                ];
                
                if (!empty($item['expiry_date'])) {
                    $itemData['expiry_date'] = $item['expiry_date'];
                }
                
                $stockUpdated = $this->deductStock($item['medicine_id'], $item['batch_no'], $item['quantity']);
                if ($stockUpdated == 0) {
                    throw new Exception("Insufficient stock for medicine");
                }
                
                $this->db->insert('sale_items', $itemData);
                $total += $itemData['total'];
            }
            
            $grandTotal = $total - $data['discount'] + $data['tax'];
            $changeAmount = $data['paid_amount'] - $grandTotal;
            
            $this->db->update('sales', [
                'subtotal' => $total,
                'grand_total' => $grandTotal,
                'change_amount' => $changeAmount > 0 ? $changeAmount : 0
            ], 'id = ?', [$saleId]);
            
            $this->db->commit();
            return $saleId;
            
        } catch (Exception $e) {
            error_log("Sale create error: " . $e->getMessage());
            $this->db->rollback();
            return false;
        }
    }
    
    public function createReturn($originalSaleId, $items, $userId) {
        $originalSale = $this->getById($originalSaleId);
        
        $this->db->beginTransaction();
        
        try {
            $invoiceNo = $this->generateInvoiceNo() . '-RET';
            $total = 0;
            
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['rate'];
            }
            
            foreach ($items as $item) {
                $batchNo = $item['batch_no'] ?? '';
                $medId = $item['medicine_id'];
                $qty = $item['quantity'];
                $added = $this->addStock($medId, $batchNo, $qty);
                if ($added == 0) {
                    throw new Exception("Failed to return stock for medicine ID: $medId, batch: $batchNo");
                }
            }
            
            $data = [
                'invoice_no' => $invoiceNo,
                'sale_date' => date('Y-m-d'),
                'subtotal' => $total,
                'grand_total' => $total,
                'sale_type' => 'return',
                'payment_method' => $originalSale['payment_method'],
                'paid_amount' => $total,
                'created_by' => $userId
            ];
            
            $saleId = $this->db->insert('sales', $data);
            
            foreach ($items as $item) {
                $itemData = [
                    'sale_id' => $saleId,
                    'medicine_id' => $item['medicine_id'],
                    'batch_no' => $item['batch_no'] ?? '',
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total' => $item['quantity'] * $item['rate']
                ];
                if (!empty($item['expiry_date'])) {
                    $itemData['expiry_date'] = $item['expiry_date'];
                }
                $this->db->insert('sale_items', $itemData);
            }
            
            $this->db->commit();
            return $saleId;
            
        } catch (Exception $e) {
            error_log("Return error: " . $e->getMessage());
            $this->db->rollback();
            return false;
        }
    }
    
    private function deductStock($medicineId, $batchNo, $quantity) {
        if (empty($batchNo)) {
            $sql = "UPDATE medicine_prices SET quantity = quantity - ? 
                    WHERE medicine_id = ? AND quantity >= ? 
                    ORDER BY expiry_date ASC LIMIT 1";
            
            $stmt = $this->db->conn->prepare($sql);
            $stmt->bind_param("iii", $quantity, $medicineId, $quantity);
        } else {
            $sql = "UPDATE medicine_prices SET quantity = quantity - ? 
                    WHERE medicine_id = ? AND batch_no = ? AND quantity >= ?";
            
            $stmt = $this->db->conn->prepare($sql);
            $stmt->bind_param("issi", $quantity, $medicineId, $batchNo, $quantity);
        }
        $stmt->execute();
        
        $affected = $stmt->affected_rows;
        error_log("DeductStock: medicine_id=$medicineId, batch=$batchNo, qty=$quantity, affected=$affected");
        
        return $affected;
    }
    
    private function addStock($medicineId, $batchNo, $quantity) {
        if (!empty($batchNo)) {
            $currentBatch = $this->db->selectOne(
                "SELECT id FROM medicine_prices WHERE medicine_id = ? AND batch_no = ?",
                [$medicineId, $batchNo]
            );
            
            if ($currentBatch) {
                $sql = "UPDATE medicine_prices SET quantity = quantity + ? WHERE id = ?";
                $stmt = $this->db->conn->prepare($sql);
                $stmt->bind_param("ii", $quantity, $currentBatch['id']);
                $stmt->execute();
                error_log("Updated existing batch $batchNo, qty added: $quantity");
                return $stmt->affected_rows;
            } else {
                $this->db->insert('medicine_prices', [
                    'medicine_id' => $medicineId,
                    'batch_no' => $batchNo,
                    'quantity' => $quantity,
                    'expiry_date' => date('Y-m-d', strtotime('+1 year'))
                ]);
                error_log("Created new batch $batchNo for return");
                return 1;
            }
        } else {
            $sql = "UPDATE medicine_prices SET quantity = quantity + ? 
                    WHERE medicine_id = ? AND expiry_date >= CURDATE()
                    ORDER BY expiry_date ASC LIMIT 1";
            
            $stmt = $this->db->conn->prepare($sql);
            $stmt->bind_param("ii", $quantity, $medicineId);
            $stmt->execute();
            return $stmt->affected_rows;
        }
    }
    
    private function generateInvoiceNo() {
        $prefix = 'INV' . date('Ymd');
        $row = $this->db->selectOne(
            "SELECT invoice_no FROM sales WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($row) {
            $num = intval(substr($row['invoice_no'], -4)) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    public function getTodaySales() {
        return $this->db->selectOne(
            "SELECT COUNT(*) as total_invoices, COALESCE(SUM(grand_total), 0) as total_amount
             FROM sales 
             WHERE sale_date = CURDATE() AND sale_type = 'sale'"
        );
    }
    
    public function getSalesByUser($userId, $fromDate = '', $toDate = '') {
        $sql = "SELECT * FROM sales WHERE created_by = " . intval($userId);
        
        if ($fromDate) {
            $sql .= " AND sale_date >= '" . $this->db->escape($fromDate) . "'";
        }
        if ($toDate) {
            $sql .= " AND sale_date <= '" . $this->db->escape($toDate) . "'";
        }
        
        $sql .= " ORDER BY id DESC";
        return $this->db->select($sql);
    }
    
    public function searchByInvoice($invoiceNo) {
        $invoiceNo = trim($invoiceNo);
        if (empty($invoiceNo)) {
            return null;
        }
        $escaped = $this->db->escape($invoiceNo);
        $sql = "SELECT s.*, u.name as created_by_name
                FROM sales s
                LEFT JOIN users u ON s.created_by = u.id
                WHERE s.invoice_no LIKE '%{$escaped}%' LIMIT 1";
        $result = $this->db->selectOne($sql);
        return $result;
    }
    
    public function getAllInvoices() {
        return $this->db->select("SELECT * FROM sales ORDER BY id DESC LIMIT 50");
    }
    
    public function delete($id) {
        $items = $this->getItems($id);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($items as $item) {
                $this->addStock($item['medicine_id'], $item['batch_no'], $item['quantity']);
            }
            
            $this->db->delete('sale_items', 'sale_id = ?', [$id]);
            $result = $this->db->delete('sales', 'id = ?', [$id]);
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
