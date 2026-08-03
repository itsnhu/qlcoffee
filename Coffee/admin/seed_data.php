<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (php_sapi_name() !== 'cli') {
    requireAdmin();
}

echo "<html><body style='font-family: sans-serif; padding: 50px;'>";
echo "<h2>Hệ thống tạo dữ liệu mẫu cho Thống kê</h2>";

try {
    $productData = fetchAll($pdo, "SELECT id, price FROM products");
    if (empty($productData)) {
        die("<p style='color:red;'>Lỗi: Không tìm thấy sản phẩm nào. Vui lòng thêm sản phẩm trước khi tạo dữ liệu.</p>");
    }

    $tableIds = array_column(fetchAll($pdo, "SELECT id FROM tables"), 'id');
    $staffId = $_SESSION['user_id'] ?? 1;

    $currentYear = 2026;
    $totalOrdersCreated = 0;

    for ($month = 1; $month <= 12; $month++) {
        $orderCount = rand(20, 40); // Increased for better visuals
        
        for ($i = 0; $i < $orderCount; $i++) {
            $day = rand(1, 28);
            $hour = rand(7, 22);
            $minute = rand(0, 59);
            $createdAt = sprintf("%04d-%02d-%02d %02d:%02d:00", $currentYear, $month, $day, $hour, $minute);
            
            $orderCode = "ORD-" . strtoupper(bin2hex(random_bytes(3)));
            $tableId = (rand(1, 100) > 40) ? ($tableIds[array_rand($tableIds)] ?? null) : null; 
            
            // Insert Order
            $stmt = $pdo->prepare("INSERT INTO orders (order_code, user_id, table_id, total_amount, status, created_at, updated_at) VALUES (?, ?, ?, 0, 'paid', ?, ?)");
            $stmt->execute([$orderCode, $staffId, $tableId, $createdAt, $createdAt]);
            $orderId = $pdo->lastInsertId();

            // Insert Items
            $numItems = rand(1, 4);
            $totalAmount = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $productData[array_rand($productData)];
                $qty = rand(1, 5);
                $subtotal = $product['price'] * $qty;
                
                $stmtDet = $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, price, subtotal, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtDet->execute([$orderId, $product['id'], $qty, $product['price'], $subtotal, $createdAt]);
                
                $totalAmount += $subtotal;
            }

            // Update Total
            $stmtUpdate = $pdo->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
            $stmtUpdate->execute([$totalAmount, $orderId]);
            $totalOrdersCreated++;
        }
        echo "<p>Đã tạo xong dữ liệu cho tháng <b>$month/$currentYear</b>...</p>";
    }

    echo "<h3 style='color:green;'>Hoàn tất! Đã thêm $totalOrdersCreated đơn hàng vào hệ thống cho năm $currentYear.</h3>";
    echo "<a href='reports/sales.php' style='display:inline-block; padding: 10px 20px; background:#6366f1; color:white; text-decoration:none; border-radius:5px;'>Quay lại trang Thống kê</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
