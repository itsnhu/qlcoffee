<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Only staff/admin can upload
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $file = $_FILES['file'];
    
    // Check if product exists
    $product = fetchOne($pdo, "SELECT * FROM products WHERE id = ?", [$id]);
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    // Upload Dir
    $uploadDir = dirname(__DIR__) . '/assets/img/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($fileExt, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Định dạng không hợp lệ (JPG, PNG, WEBP)']);
        exit;
    }
    
    $newName = time() . '_' . uniqid() . '.' . $fileExt;
    $targetPath = $uploadDir . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Update DB
        $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
        $stmt->execute([$newName, $id]);
        
        echo json_encode([
            'success' => true, 
            'image_url' => BASE_URL . '/assets/img/products/' . $newName,
            'image_name' => $newName
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu tệp']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không đầy đủ']);
}
?>
