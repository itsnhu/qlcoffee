<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'requireLogin' => true, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$customerId = $_SESSION['customer_id'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        $medicineId = (int)($data['medicine_id'] ?? 0);
        $quantity = max(1, (int)($data['quantity'] ?? 1));

        if (!$medicineId) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            exit;
        }

        // Check if medicine exists and is available
        $medicine = fetchOne($pdo, "SELECT * FROM medicines WHERE id = ? AND quantity > 0 AND expiry_date > CURDATE()", [$medicineId]);
        if (!$medicine) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã hết hàng']);
            exit;
        }

        // Check stock
        $existingCart = fetchOne($pdo, "SELECT * FROM cart WHERE customer_id = ? AND medicine_id = ?", [$customerId, $medicineId]);
        $totalQty = ($existingCart['quantity'] ?? 0) + $quantity;

        if ($totalQty > $medicine['quantity']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
            exit;
        }

        // Add or update cart
        if ($existingCart) {
            executeQuery($pdo, "UPDATE cart SET quantity = ? WHERE id = ?", [$totalQty, $existingCart['id']]);
        } else {
            executeQuery($pdo, "INSERT INTO cart (customer_id, medicine_id, quantity) VALUES (?, ?, ?)", [$customerId, $medicineId, $quantity]);
        }

        // Get cart count
        $cartCount = fetchOne($pdo, "SELECT SUM(quantity) as count FROM cart WHERE customer_id = ?", [$customerId]);

        echo json_encode(['success' => true, 'cartCount' => $cartCount['count'] ?? 0]);
        break;

    case 'update':
        $medicineId = (int)($data['medicine_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 0);

        if ($quantity <= 0) {
            // Remove from cart
            executeQuery($pdo, "DELETE FROM cart WHERE customer_id = ? AND medicine_id = ?", [$customerId, $medicineId]);
        } else {
            // Check stock
            $medicine = fetchOne($pdo, "SELECT quantity FROM medicines WHERE id = ?", [$medicineId]);
            if ($quantity > $medicine['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
                exit;
            }
            executeQuery($pdo, "UPDATE cart SET quantity = ? WHERE customer_id = ? AND medicine_id = ?", [$quantity, $customerId, $medicineId]);
        }

        // Get new totals
        $cartItems = fetchAll($pdo, "
            SELECT c.*, m.price
            FROM cart c
            JOIN medicines m ON c.medicine_id = m.id
            WHERE c.customer_id = ?
        ", [$customerId]);

        $cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
        $cartCount = array_sum(array_column($cartItems, 'quantity'));

        echo json_encode([
            'success' => true,
            'cartTotal' => $cartTotal,
            'cartCount' => $cartCount,
            'cartTotalFormatted' => formatCurrency($cartTotal)
        ]);
        break;

    case 'remove':
        $medicineId = (int)($data['medicine_id'] ?? 0);
        executeQuery($pdo, "DELETE FROM cart WHERE customer_id = ? AND medicine_id = ?", [$customerId, $medicineId]);

        $cartCount = fetchOne($pdo, "SELECT SUM(quantity) as count FROM cart WHERE customer_id = ?", [$customerId]);
        echo json_encode(['success' => true, 'cartCount' => $cartCount['count'] ?? 0]);
        break;

    case 'clear':
        executeQuery($pdo, "DELETE FROM cart WHERE customer_id = ?", [$customerId]);
        echo json_encode(['success' => true, 'cartCount' => 0]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
?>
