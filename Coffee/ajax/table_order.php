<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Auto-migration: đảm bảo bảng orders có đủ cột cần thiết
try {
    $cols = array_column(fetchAll($pdo, "SHOW COLUMNS FROM orders"), 'Field');
    
    if (!in_array('table_id', $cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN table_id INT NULL AFTER customer_id");
    }
    if (!in_array('user_id', $cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN user_id INT NULL AFTER order_code");
    }
    if (!in_array('payment_status', $cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid') DEFAULT 'unpaid' AFTER status");
    }
    if (!in_array('payment_method', $cols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method ENUM('cash','transfer','cod') DEFAULT 'cash' AFTER payment_status");
    }
} catch (PDOException $e) {
    error_log("Migration add columns: " . $e->getMessage());
}

// Cho phép các cột NULL và cập nhật ENUM (mỗi lệnh riêng để không ảnh hưởng nhau)
$alterStatements = [
    "ALTER TABLE orders MODIFY COLUMN customer_id INT NULL",
    "ALTER TABLE orders MODIFY COLUMN customer_name VARCHAR(100) NULL",
    "ALTER TABLE orders MODIFY COLUMN customer_phone VARCHAR(20) NULL",
    "ALTER TABLE orders MODIFY COLUMN shipping_address TEXT NULL",
    "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','preparing','served','shipping','completed','paid','cancelled') DEFAULT 'pending'"
];

// Xoá foreign key customer_id nếu có
try {
    $fks = fetchAll($pdo, "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
    foreach ($fks as $fk) {
        $pdo->exec("ALTER TABLE orders DROP FOREIGN KEY " . $fk['CONSTRAINT_NAME']);
    }
} catch (PDOException $e) {
    error_log("Migration drop FK: " . $e->getMessage());
}

foreach ($alterStatements as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { error_log("Migration: " . $e->getMessage()); }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_order':
        // Get current active order for a table
        $table_id = (int)($_GET['table_id'] ?? 0);
        if ($table_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bàn không hợp lệ']);
            exit;
        }

        try {
            // Get active order (pending or preparing) for this table
            $order = fetchOne($pdo, "SELECT * FROM orders WHERE table_id = ? AND status IN ('pending','preparing','served') ORDER BY created_at DESC LIMIT 1", [$table_id]);
            
            $items = [];
            if ($order) {
                $items = fetchAll($pdo, "SELECT od.*, p.name as product_name 
                                        FROM order_details od 
                                        JOIN products p ON od.product_id = p.id 
                                        WHERE od.order_id = ?", [$order['id']]);
            }

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        break;

    case 'add_item':
        $table_id = (int)($_POST['table_id'] ?? 0);
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($table_id <= 0 || $product_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Get product info
            $product = fetchOne($pdo, "SELECT * FROM products WHERE id = ? AND is_available = 1", [$product_id]);
            if (!$product) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã hết']);
                exit;
            }

            // Find or create active order for this table
            $order = fetchOne($pdo, "SELECT * FROM orders WHERE table_id = ? AND status IN ('pending','preparing','served') ORDER BY created_at DESC LIMIT 1", [$table_id]);

            if (!$order) {
                // Create new order
                $orderCode = 'TB' . $table_id . '-' . date('dHi') . rand(10, 99);
                executeQuery($pdo, "INSERT INTO orders (order_code, user_id, table_id, total_amount, status, created_at) VALUES (?, ?, ?, 0, 'pending', NOW())", 
                    [$orderCode, $_SESSION['user_id'], $table_id]);
                $orderId = $pdo->lastInsertId();

                // Update table status to busy
                executeQuery($pdo, "UPDATE tables SET status = 'busy' WHERE id = ?", [$table_id]);
            } else {
                $orderId = $order['id'];
            }

            // Check if item already exists in order
            $existingItem = fetchOne($pdo, "SELECT * FROM order_details WHERE order_id = ? AND product_id = ?", [$orderId, $product_id]);

            $subtotal = $product['price'] * $quantity;

            if ($existingItem) {
                // Update quantity
                $newQty = $existingItem['quantity'] + $quantity;
                $newSubtotal = $product['price'] * $newQty;
                executeQuery($pdo, "UPDATE order_details SET quantity = ?, subtotal = ? WHERE id = ?", 
                    [$newQty, $newSubtotal, $existingItem['id']]);
            } else {
                // Insert new item
                executeQuery($pdo, "INSERT INTO order_details (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)", 
                    [$orderId, $product_id, $quantity, $product['price'], $subtotal]);
            }

            // Recalculate order total
            $total = fetchOne($pdo, "SELECT SUM(subtotal) as total FROM order_details WHERE order_id = ?", [$orderId]);
            executeQuery($pdo, "UPDATE orders SET total_amount = ? WHERE id = ?", [$total['total'], $orderId]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Đã thêm món thành công!']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        break;

    case 'checkout':
        $table_id = (int)($_POST['table_id'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $service_type = $_POST['service_type'] ?? 'dine_in';
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $customer_name = $_POST['customer_name'] ?? null;
        $customer_phone = $_POST['customer_phone'] ?? null;

        // Validate payment method
        if (!in_array($payment_method, ['cash', 'transfer'])) {
            $payment_method = 'cash';
        }

        if ($table_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bàn không hợp lệ']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $order = fetchOne($pdo, "SELECT * FROM orders WHERE table_id = ? AND status IN ('pending','preparing','served') ORDER BY created_at DESC LIMIT 1", [$table_id]);

            if (!$order) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Bàn này chưa có hoá đơn!']);
                exit;
            }

            // Apply discount
            $finalAmount = $order['total_amount'];
            if ($discount > 0 && $discount <= 100) {
                $finalAmount = $finalAmount * (1 - $discount / 100);
            }

            // Update order status with payment method
            $methodLabel = ($payment_method === 'transfer') ? 'Chuyển khoản' : 'Tiền mặt';
            executeQuery($pdo, "UPDATE orders SET status = 'paid', payment_status = 'paid', payment_method = ?, total_amount = ?, note = ?, customer_name = ?, customer_phone = ? WHERE id = ?", 
                [$payment_method, $finalAmount, 'Giảm giá: ' . $discount . '% - ' . $service_type . ' - ' . $methodLabel, $customer_name, $customer_phone, $order['id']]);

            // Free the table
            executeQuery($pdo, "UPDATE tables SET status = 'free' WHERE id = ?", [$table_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Thanh toán thành công (' . $methodLabel . ')! Tổng: ' . number_format($finalAmount, 0, ',', '.') . ' ₫']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        break;

    case 'transfer':
        $from_table_id = (int)($_POST['from_table_id'] ?? 0);
        $to_table_id = (int)($_POST['to_table_id'] ?? 0);

        if ($from_table_id <= 0 || $to_table_id <= 0 || $from_table_id === $to_table_id) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn bàn hợp lệ']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Check destination table is free
            $destTable = fetchOne($pdo, "SELECT * FROM tables WHERE id = ?", [$to_table_id]);
            if (!$destTable || $destTable['status'] === 'busy') {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Bàn đích đang có khách, không thể chuyển!']);
                exit;
            }

            // Find active order on source table
            $order = fetchOne($pdo, "SELECT * FROM orders WHERE table_id = ? AND status IN ('pending','preparing','served') ORDER BY created_at DESC LIMIT 1", [$from_table_id]);

            if (!$order) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Bàn nguồn chưa có hoá đơn để chuyển!']);
                exit;
            }

            // Transfer order
            executeQuery($pdo, "UPDATE orders SET table_id = ? WHERE id = ?", [$to_table_id, $order['id']]);

            // Update table statuses
            executeQuery($pdo, "UPDATE tables SET status = 'free' WHERE id = ?", [$from_table_id]);
            executeQuery($pdo, "UPDATE tables SET status = 'busy' WHERE id = ?", [$to_table_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Chuyển bàn thành công!']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        break;

    case 'delete_item':
        $item_id = (int)($_POST['item_id'] ?? 0);

        if ($item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $item = fetchOne($pdo, "SELECT od.*, o.table_id FROM order_details od JOIN orders o ON od.order_id = o.id WHERE od.id = ?", [$item_id]);
            if (!$item) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy món']);
                exit;
            }

            $orderId = $item['order_id'];
            executeQuery($pdo, "DELETE FROM order_details WHERE id = ?", [$item_id]);

            // Check if order still has items
            $remaining = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM order_details WHERE order_id = ?", [$orderId]);

            if ($remaining['cnt'] == 0) {
                // Delete empty order and free table
                executeQuery($pdo, "DELETE FROM orders WHERE id = ?", [$orderId]);
                executeQuery($pdo, "UPDATE tables SET status = 'free' WHERE id = ?", [$item['table_id']]);
            } else {
                // Recalculate total
                $total = fetchOne($pdo, "SELECT SUM(subtotal) as total FROM order_details WHERE order_id = ?", [$orderId]);
                executeQuery($pdo, "UPDATE orders SET total_amount = ? WHERE id = ?", [$total['total'], $orderId]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Đã xoá món']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        break;

    case 'update_customer':
        $table_id = (int)($_POST['table_id'] ?? 0);
        $customer_name = $_POST['customer_name'] ?? null;
        $customer_phone = $_POST['customer_phone'] ?? null;

        if ($table_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bàn không hợp lệ']);
            exit;
        }

        try {
            $order = fetchOne($pdo, "SELECT * FROM orders WHERE table_id = ? AND status IN ('pending','preparing','served') ORDER BY created_at DESC LIMIT 1", [$table_id]);
            if ($order) {
                executeQuery($pdo, "UPDATE orders SET customer_name = ?, customer_phone = ? WHERE id = ?", [$customer_name, $customer_phone, $order['id']]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}
