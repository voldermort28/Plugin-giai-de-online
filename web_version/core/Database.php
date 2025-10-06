<?php
// web_version/core/Database.php

class Database {
    private $pdo;

    public function __construct($pdo_connection) {
        $this->pdo = $pdo_connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage() . " SQL: " . $sql);
        }
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $where_params) {
        $set_parts = [];
        foreach ($data as $column => $value) {
            $set_parts[] = "{$column} = ?";
        }
        $set_clause = implode(', ', $set_parts);
        $sql = "UPDATE {$table} SET {$set_clause} WHERE {$where}";
        $params = array_merge(array_values($data), $where_params);
        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $where_params) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $where_params)->rowCount();
    }
}