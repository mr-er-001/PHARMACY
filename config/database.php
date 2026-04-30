<?php
require_once __DIR__ . '/constants.php';

class Database {
    public $conn;
    private $stmt;
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public function query($sql, $params = []) {
        $this->stmt = $this->conn->prepare($sql);
        
        if (!$this->stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $this->stmt->bind_param($types, ...$params);
        }
        
        $this->stmt->execute();
        return $this->stmt->get_result();
    }
    
    public function select($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if (!$result) {
            return [];
        }
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    public function selectOne($sql, $params = []) {
        $rows = $this->select($sql, $params);
        return !empty($rows) ? $rows[0] : null;
    }
    
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        
        $this->stmt = $this->conn->prepare($sql);
        
        if (!$this->stmt) {
            error_log("Prepare failed: " . $this->conn->error . " SQL: " . $sql);
            return false;
        }
        
        $values = array_values($data);
        $types = str_repeat('s', count($values));
        $this->stmt->bind_param($types, ...$values);
        
        if ($this->stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        error_log("Execute failed: " . $this->stmt->error);
        return false;
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = ?";
        }
        
        $setClause = implode(', ', $sets);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $this->stmt = $this->conn->prepare($sql);
        
        $values = array_merge(array_values($data), $whereParams);
        $types = str_repeat('s', count($values));
        $this->stmt->bind_param($types, ...$values);
        
        return $this->stmt->execute();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $this->stmt = $this->conn->prepare($sql);
        $this->stmt->bind_param(str_repeat('s', count($params)), ...$params);
        
        return $this->stmt->execute();
    }
    
    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
    
    public function getLastId() {
        return $this->conn->insert_id;
    }
    
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    public function commit() {
        $this->conn->commit();
    }
    
    public function rollback() {
        $this->conn->rollback();
    }
    
    public function getError() {
        $error = $this->conn->error;
        if ($this->stmt && $this->stmt->error) {
            $error .= ' | Statement: ' . $this->stmt->error;
        }
        return $error;
    }
    
    public function __destruct() {
        if ($this->stmt) {
            $this->stmt->close();
        }
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

$db = new Database();
$GLOBALS['db'] = $db;
