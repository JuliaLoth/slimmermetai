<?php
require_once 'config.php';

function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connectie mislukt: " . $e->getMessage());
    }
}

// Database functies
function query($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insert($table, $data) {
    $conn = getDBConnection();
    $fields = array_keys($data);
    $values = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $values) . ")";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_values($data));
    return $conn->lastInsertId();
}

function update($table, $data, $where, $whereParams = []) {
    $conn = getDBConnection();
    $set = array_map(function($field) {
        return "$field = ?";
    }, array_keys($data));
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
    
    $stmt = $conn->prepare($sql);
    $params = array_merge(array_values($data), $whereParams);
    return $stmt->execute($params);
}

function delete($table, $where, $params = []) {
    $conn = getDBConnection();
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}
?> 