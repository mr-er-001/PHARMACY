<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Vendor.php';

class Purchase {
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['db'];
    }
    
    public function getAll($fromDate = '', $toDate = '', $vendorId = '') {
        $sql = "SELECT pi.*, v.name as vendor_name, u.name as created_by_name
                FROM purchase_invoices pi
                LEFT JOIN vendors v ON pi.vendor_id = v.id
                LEFT JOIN users u ON pi.created_by = u.id
                WHERE 1=1";
        
        if ($fromDate) {
            $sql .= " AND pi.invoice_date >= '" . $this->db->escape($fromDate) . "'";
        }
        if ($toDate) {
            $sql .= " AND pi.invoice_date <= '" . $this->db->escape($toDate) . "'";
        }
        if ($vendorId) {
            $sql .= " AND pi.vendor_id = '" . $this->db->escape($vendorId) . "'";
        }
        
        $sql .= " ORDER BY pi.id DESC";
        return $this->db->select($sql);
    }
    
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT pi.*, v.name as vendor_name, v.company_name, u.name as created_by_name
             FROM purchase_invoices pi
             LEFT JOIN vendors v ON pi.vendor_id = v.id
             LEFT JOIN users u ON pi.created_by = u.id
             WHERE pi.id = ?",
            [$id]
        );
    }
    
    public function getItems($invoiceId) {
        return $this->db->select(
            "SELECT pi.*, m.name as medicine_name, b.name as brand_name, c.name as category_name
             FROM purchase_items pi
             JOIN medicines m ON pi.medicine_id = m.id
             LEFT JOIN brands b ON m.brand_id = b.id
             LEFT JOIN categories c ON m.category_id = c.id
             WHERE pi.purchase_invoice_id = ?",
            [$invoiceId]
        );
    }
    
    public function create($data, $items) {
        $this->db->beginTransaction();
        
        try {
            $invoiceNo = $this->generateInvoiceNo();
            $data['invoice_no'] = $invoiceNo;
            
            $invoiceId = $this->db->insert('purchase_invoices', $data);
            
            if (!$invoiceId) {
                throw new Exception("Failed to create invoice: " . $this->db->getError());
            }
            
            $total = 0;
            foreach ($items as $item) {
                $batchNo = $item['batch_no'] ?? '';
                
                $result = $this->db->insert('purchase_items', [
                    'purchase_invoice_id' => $invoiceId,
                    'medicine_id' => $item['medicine_id'],
                    'batch_no' => $batchNo,
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'total' => $item['quantity'] * $item['rate']
                ]);
                
                if (!$result) {
                    throw new Exception("Failed to add item: " . $this->db->getError());
                }
                
                $itemTotal = $item['quantity'] * $item['rate'];
                $total += $itemTotal;
                
                $stockResult = $this->updateMedicineStock($item['medicine_id'], $item['quantity'], $item['rate']);
                if (!$stockResult) {
                    throw new Exception("Failed to update stock: " . $this->db->getError());
                }
            }
            
            $grandTotal = $total - $data['discount'] + $data['tax'];
            
            $updateResult = $this->db->update('purchase_invoices', [
                'total_amount' => $total,
                'grand_total' => $grandTotal,
                'payment_status' => $data['payment_status'] ?? 'pending'
            ], 'id = ?', [$invoiceId]);
            
            if (!$updateResult) {
                throw new Exception("Failed to update invoice: " . $this->db->getError());
            }
            
            $vendor = new Vendor();
            $vendor->addPurchaseEntry($data['vendor_id'], $invoiceId, $grandTotal);
            
            $this->db->commit();
            return $invoiceId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Purchase Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function updateMedicineStock($medicineId, $quantity, $purchasePrice) {
        $batchNo = 'BATCH' . date('YmdHis') . rand(100, 999);
        
        $existing = $this->db->selectOne(
            "SELECT id, quantity FROM medicine_prices WHERE medicine_id = ?",
            [$medicineId]
        );
        
        if ($existing) {
            $this->db->query(
                "UPDATE medicine_prices SET quantity = quantity + ? WHERE id = ?",
                [$quantity, $existing['id']]
            );
            return true;
        } else {
            $data = [
                'medicine_id' => $medicineId,
                'batch_no' => $batchNo,
                'purchase_price' => floatval($purchasePrice),
                'selling_price' => floatval($purchasePrice * 1.15),
                'quantity' => intval($quantity)
            ];
            
            $insertResult = $this->db->insert('medicine_prices', $data);
            if (!$insertResult) {
                error_log("Stock insert failed for medicine_id=$medicineId: " . $this->db->getError());
            }
            return $insertResult;
        }
    }
    
    private function generateInvoiceNo() {
        $prefix = 'PI' . date('Ymd');
        $row = $this->db->selectOne(
            "SELECT invoice_no FROM purchase_invoices WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($row) {
            $num = intval(substr($row['invoice_no'], -4)) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    public function updatePaymentStatus($id, $status) {
        return $this->db->update('purchase_invoices', ['payment_status' => $status], 'id = ?', [$id]);
    }
    
    public function delete($id) {
        $items = $this->getItems($id);
        $invoice = $this->getById($id);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($items as $item) {
                $batchNo = $item['batch_no'] ?? '';
                if (!empty($batchNo)) {
                    $this->db->query(
                        "UPDATE medicine_prices SET quantity = quantity - ? WHERE medicine_id = ? AND batch_no = ?",
                        [$item['quantity'], $item['medicine_id'], $batchNo]
                    );
                } else {
                    $this->db->query(
                        "UPDATE medicine_prices SET quantity = quantity - ? WHERE medicine_id = ? ORDER BY expiry_date DESC LIMIT 1",
                        [$item['quantity'], $item['medicine_id']]
                    );
                }
            }
            
            $this->db->delete('purchase_items', 'purchase_invoice_id = ?', [$id]);
            
            if ($invoice) {
                $this->db->query(
                    "DELETE FROM vendor_ledger WHERE reference_id = ? AND transaction_type = 'purchase'",
                    [$id]
                );
            }
            
            $result = $this->db->delete('purchase_invoices', 'id = ?', [$id]);
            
            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Purchase delete error: " . $e->getMessage());
            return false;
        }
    }
}
