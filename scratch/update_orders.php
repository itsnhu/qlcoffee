<?php
require 'config/database.php';
$orders = fetchAll($pdo, "SELECT id, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
foreach ($orders as $order) {
    executeQuery($pdo, "UPDATE orders SET created_at = NOW() WHERE id = ?", [$order['id']]);
    echo "Updated order {$order['id']} to today.\n";
}
?>
