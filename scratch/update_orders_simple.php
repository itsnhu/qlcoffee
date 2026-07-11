<?php
$dsn = "mysql:host=localhost;dbname=coffeeshop_db;charset=utf8mb4";
$pdo = new PDO($dsn, "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$orders = $pdo->query("SELECT id FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($orders as $order) {
    $stmt = $pdo->prepare("UPDATE orders SET created_at = NOW() WHERE id = ?");
    $stmt->execute([$order['id']]);
    echo "Updated order id " . $order['id'] . "\n";
}
?>
