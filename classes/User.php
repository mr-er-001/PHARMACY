<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = $GLOBALS['db'];
    }
    
    public function login($email, $password) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    public function getAll($search = '') {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (name LIKE ? OR email LIKE ?)";
            $params = ["%$search%", "%$search%"];
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->select($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->db->insert('users', $data);
    }
    
    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete('users', 'id = ?', [$id]);
    }
    
    public function updatePassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->db->update('users', ['password' => $hash], 'id = ?', [$id]);
    }
}
