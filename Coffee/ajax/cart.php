<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

// Check login if storing in DB
$userId = $_SESSION['customer_id'] ?? null;

// Check and add size column if not exists
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM cart")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('size', $checkCols)) {
        $pdo->exec("ALTER TABLE cart ADD COLUMN size VARCHAR(10) DEFAULT 'M' AFTER product_id");
    }
    
    // Update unique constraint to include size
    // First, check if the old unique constraint exists
    $constraints = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'cart' 
        AND CONSTRAINT_TYPE = 'UNIQUE'
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('unique_cart_item', $constraints)) {
        $pdo->exec("ALTER TABLE cart DROP INDEX unique_cart_item");
        $pdo->exec("ALTER TABLE cart ADD UNIQUE KEY unique_cart_size (customer_id, product_id, size)");
    } elseif (!in_array('unique_cart_size', $constraints)) {
        $pdo->exec("ALTER TABLE cart ADD UNIQUE KEY unique_cart_size (customer_id, product_id, size)");
    }
} catch (Exception $e) {
    // Silently handle if error occurs (e.g. index already exists)
}

if ($action === 'add') {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);
    $size = strtoupper($input['size'] ?? 'M');

    if ($productId > 0 && $quantity > 0) {
        // Check product stock
        $product = fetchOne($pdo, "SELECT * FROM products WHERE id = ?", [$productId]);
        if (!$product || $product['quantity'] < $quantity) {
             echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ số lượng']);
             exit;
        }

        // Get correct price based on size
        $price = $product['price'];
        if ($size === 'S' && $product['has_s']) $price = $product['price_s'];
        elseif ($size === 'M' && $product['has_m']) $price = $product['price_m'];
        elseif ($size === 'L' && $product['has_l']) $price = $product['price_l'];
        elseif ($size === 'XL' && $product['has_xl']) $price = $product['price_xl'];

        if ($userId) {
            // Add to DB Cart
            $existing = fetchOne($pdo, "SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ? AND size = ?", [$userId, $productId, $size]);
            
            if ($existing) {
                $newQty = $existing['quantity'] + $quantity;
                executeQuery($pdo, "UPDATE cart SET quantity = ? WHERE id = ?", [$newQty, $existing['id']]);
            } else {
                executeQuery($pdo, "INSERT INTO cart (customer_id, product_id, quantity, size) VALUES (?, ?, ?, ?)", [$userId, $productId, $quantity, $size]);
            }
            $response['success'] = true;
            $response['message'] = 'Added to cart';
        } else {
            // Add to Session Cart
            // Structure: [product_id_size => ['id' => ..., 'quantity' => ..., 'size' => ...]]
            $cartKey = $productId . '_' . $size;
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            
            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'id' => $productId,
                    'quantity' => $quantity,
                    'size' => $size,
                    'price' => $price
                ];
            }
            $response['success'] = true;
        }
    }
} elseif ($action === 'update') {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);
    $size = strtoupper($input['size'] ?? 'M');

    if ($productId > 0 && $quantity > 0) {
        if ($userId) {
            executeQuery($pdo, "UPDATE cart SET quantity = ? WHERE customer_id = ? AND product_id = ? AND size = ?", [$quantity, $userId, $productId, $size]);
        } else {
            $cartKey = $productId . '_' . $size;
            if (isset($_SESSION['cart'][$cartKey])) {
                 $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            }
        }
        $response['success'] = true;
    }
} elseif ($action === 'remove') {
    $productId = (int)($input['product_id'] ?? 0);
    $size = strtoupper($input['size'] ?? 'M');

    if ($productId > 0) {
        if ($userId) {
            executeQuery($pdo, "DELETE FROM cart WHERE customer_id = ? AND product_id = ? AND size = ?", [$userId, $productId, $size]);
        } else {
            $cartKey = $productId . '_' . $size;
            unset($_SESSION['cart'][$cartKey]);
        }
        $response['success'] = true;
    }
} elseif ($action === 'clear') {
    if ($userId) {
        executeQuery($pdo, "DELETE FROM cart WHERE customer_id = ?", [$userId]);
    } else {
        $_SESSION['cart'] = [];
    }
    $response['success'] = true;
}

// Calculate totals for response
$cartCount = 0;
$cartTotal = 0;

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($userId) {
    // DB Cart
    try {
        $cartData = fetchAll($pdo, "SELECT c.quantity, c.size, p.price, p.price_s, p.price_m, p.price_l, p.price_xl, p.has_s, p.has_m, p.has_l, p.has_xl 
                                 FROM cart c JOIN products p ON c.product_id = p.id WHERE c.customer_id = ?", [$userId]);
        foreach ($cartData as $item) {
            $cartCount += $item['quantity'];
            
            // Determine price based on size
            $p = $item['price'];
            if ($item['size'] === 'S' && $item['has_s']) $p = $item['price_s'];
            elseif ($item['size'] === 'M' && $item['has_m']) $p = $item['price_m'];
            elseif ($item['size'] === 'L' && $item['has_l']) $p = $item['price_l'];
            elseif ($item['size'] === 'XL' && $item['has_xl']) $p = $item['price_xl'];
            
            $cartTotal += $item['quantity'] * $p;
        }
    } catch (Exception $e) {
        error_log("Cart calculation error: " . $e->getMessage());
    }
} else {
    // Session Cart
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
        
        $product = fetchOne($pdo, "SELECT * FROM products WHERE id = ?", [$item['id']]);
        if ($product) {
            $p = $product['price'];
            if ($item['size'] === 'S' && $product['has_s']) $p = $product['price_s'];
            elseif ($item['size'] === 'M' && $product['has_m']) $p = $product['price_m'];
            elseif ($item['size'] === 'L' && $product['has_l']) $p = $product['price_l'];
            elseif ($item['size'] === 'XL' && $product['has_xl']) $p = $product['price_xl'];
            
            $cartTotal += $item['quantity'] * $p;
        }
    }
}

$response['cartCount'] = $cartCount;
$response['cartTotal'] = $cartTotal;
$response['cartTotalFormatted'] = number_format($cartTotal, 0, ',', '.') . ' ₫';

echo json_encode($response);
?>
