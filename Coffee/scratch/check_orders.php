<?php
require_once 'config/database.php';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll();
    echo json_encode($cols);
} catch (Exception $e) {
    echo $e->getMessage();
}
