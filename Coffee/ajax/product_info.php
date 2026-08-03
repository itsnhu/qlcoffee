<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $product = fetchOne($pdo, "
        SELECT p.*, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ", [$id]);

    if ($product) {
        $product['formatted_price'] = number_format($product['price'], 0, ',', '.') . 'đ';
        // Build full image URL
        if (!empty($product['image'])) {
            if (!filter_var($product['image'], FILTER_VALIDATE_URL)) {
                $product['image'] = BASE_URL . '/assets/img/products/' . $product['image'];
            }
        }
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
