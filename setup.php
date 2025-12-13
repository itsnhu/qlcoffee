<?php
/**
 * Database Setup Script
 * Chạy file này để tạo các bảng cần thiết cho hệ thống
 * Truy cập: http://localhost/PharmaManager/setup.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$messages = [];
$errors = [];

// SQL để tạo các bảng
$tables = [
    'customers' => "
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'orders' => "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_code VARCHAR(50) NOT NULL UNIQUE,
            customer_id INT NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            shipping_address TEXT NOT NULL,
            total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            note TEXT DEFAULT NULL,
            payment_method VARCHAR(50) DEFAULT 'cod',
            status ENUM('pending', 'confirmed', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'order_details' => "
        CREATE TABLE IF NOT EXISTS order_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(15,2) NOT NULL,
            subtotal DECIMAL(15,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'cart' => "
        CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cart_item (customer_id, medicine_id),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

// Demo customer account
$demoCustomer = [
    'email' => 'khachhang@gmail.com',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'full_name' => 'Khách Hàng Demo',
    'phone' => '0901234567',
    'address' => '123 Đường ABC, Quận 1, TP.HCM'
];

// SQL để sửa bảng (thêm cột thiếu)
$alterTables = [
    "ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1",
];

// Chạy setup khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'fix_tables') {
        // Thêm cột is_active nếu chưa có
        try {
            // Check if column exists
            $result = $pdo->query("SHOW COLUMNS FROM customers LIKE 'is_active'");
            if ($result->rowCount() == 0) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN is_active TINYINT(1) DEFAULT 1");
                $messages[] = "✓ Đã thêm cột <strong>is_active</strong> vào bảng customers";
            } else {
                $messages[] = "⚠ Cột is_active đã tồn tại";
            }
        } catch (PDOException $e) {
            $errors[] = "✗ Lỗi sửa bảng: " . $e->getMessage();
        }
    }

    if ($action === 'drop_recreate') {
        // Xóa và tạo lại các bảng (giữ nguyên customers)
        try {
            // Xóa theo thứ tự (foreign key)
            $pdo->exec("DROP TABLE IF EXISTS order_details");
            $messages[] = "✓ Đã xóa bảng order_details";

            $pdo->exec("DROP TABLE IF EXISTS cart");
            $messages[] = "✓ Đã xóa bảng cart";

            $pdo->exec("DROP TABLE IF EXISTS orders");
            $messages[] = "✓ Đã xóa bảng orders";

            // Tạo lại
            foreach (['orders', 'order_details', 'cart'] as $tableName) {
                $pdo->exec($tables[$tableName]);
                $messages[] = "✓ Đã tạo lại bảng <strong>{$tableName}</strong>";
            }
        } catch (PDOException $e) {
            $errors[] = "✗ Lỗi: " . $e->getMessage();
        }
    }

    if ($action === 'create_tables') {
        foreach ($tables as $tableName => $sql) {
            try {
                $pdo->exec($sql);
                $messages[] = "✓ Tạo bảng <strong>{$tableName}</strong> thành công";
            } catch (PDOException $e) {
                $errors[] = "✗ Lỗi tạo bảng {$tableName}: " . $e->getMessage();
            }
        }
    }

    if ($action === 'create_demo') {
        try {
            // Check if demo customer exists
            $existing = fetchOne($pdo, "SELECT id FROM customers WHERE email = ?", [$demoCustomer['email']]);
            if ($existing) {
                $messages[] = "⚠ Tài khoản demo đã tồn tại";
            } else {
                executeQuery($pdo, "
                    INSERT INTO customers (email, password, full_name, phone, address)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $demoCustomer['email'],
                    $demoCustomer['password'],
                    $demoCustomer['full_name'],
                    $demoCustomer['phone'],
                    $demoCustomer['address']
                ]);
                $messages[] = "✓ Tạo tài khoản demo thành công";
            }
        } catch (PDOException $e) {
            $errors[] = "✗ Lỗi tạo tài khoản demo: " . $e->getMessage();
        }
    }

    if ($action === 'run_all') {
        // Create tables
        foreach ($tables as $tableName => $sql) {
            try {
                $pdo->exec($sql);
                $messages[] = "✓ Tạo bảng <strong>{$tableName}</strong> thành công";
            } catch (PDOException $e) {
                $errors[] = "✗ Lỗi tạo bảng {$tableName}: " . $e->getMessage();
            }
        }

        // Create demo customer
        try {
            $existing = fetchOne($pdo, "SELECT id FROM customers WHERE email = ?", [$demoCustomer['email']]);
            if (!$existing) {
                executeQuery($pdo, "
                    INSERT INTO customers (email, password, full_name, phone, address)
                    VALUES (?, ?, ?, ?, ?)
                ", [
                    $demoCustomer['email'],
                    $demoCustomer['password'],
                    $demoCustomer['full_name'],
                    $demoCustomer['phone'],
                    $demoCustomer['address']
                ]);
                $messages[] = "✓ Tạo tài khoản demo thành công";
            } else {
                $messages[] = "⚠ Tài khoản demo đã tồn tại";
            }
        } catch (PDOException $e) {
            $errors[] = "✗ Lỗi tạo tài khoản demo: " . $e->getMessage();
        }
    }
}

// Check existing tables
$existingTables = [];
try {
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
} catch (PDOException $e) {
    $errors[] = "Không thể kiểm tra bảng: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - PharmaManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-500: #14b8a6;
            --primary-600: #0d9488;
            --primary-700: #0f766e;
        }
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .setup-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .setup-header {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .setup-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .table-status {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .table-status.exists {
            background: #d1fae5;
            color: #065f46;
        }
        .table-status.missing {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-setup {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-setup:hover {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            color: white;
            transform: translateY(-2px);
        }
        .demo-box {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 8px;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="setup-card">
                    <div class="setup-header">
                        <i class="bi bi-database-fill-gear" style="font-size: 3rem;"></i>
                        <h1 class="mt-3">PharmaManager Setup</h1>
                        <p class="mb-0 opacity-75">Thiết lập cơ sở dữ liệu cho hệ thống</p>
                    </div>

                    <div class="p-4">
                        <?php if (!empty($messages)): ?>
                            <div class="alert alert-success">
                                <?php foreach ($messages as $msg): ?>
                                    <div><?= $msg ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $err): ?>
                                    <div><?= $err ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Database Status -->
                        <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Trạng thái Database</h5>
                        <div class="mb-4">
                            <?php
                            $requiredTables = ['customers', 'orders', 'order_details', 'cart'];
                            $allExist = true;
                            foreach ($requiredTables as $table):
                                $exists = in_array($table, $existingTables);
                                if (!$exists) $allExist = false;
                            ?>
                                <div class="table-status <?= $exists ? 'exists' : 'missing' ?>">
                                    <i class="bi <?= $exists ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i>
                                    <strong><?= $table ?></strong>
                                    <span class="ms-auto"><?= $exists ? 'Đã tạo' : 'Chưa tạo' ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Actions -->
                        <h5 class="mb-3"><i class="bi bi-gear me-2"></i>Thao tác</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_tables">
                                    <button type="submit" class="btn btn-setup w-100">
                                        <i class="bi bi-table me-2"></i>Tạo bảng
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST">
                                    <input type="hidden" name="action" value="fix_tables">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bi bi-wrench me-2"></i>Sửa cột
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_demo">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-person-plus me-2"></i>Tạo demo
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <form method="POST">
                                    <input type="hidden" name="action" value="run_all">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-lightning-charge me-2"></i>Chạy tất cả
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="POST" onsubmit="return confirm('Xóa và tạo lại orders, order_details, cart?\nDữ liệu đơn hàng & giỏ hàng sẽ bị xóa!')">
                                    <input type="hidden" name="action" value="drop_recreate">
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="bi bi-arrow-repeat me-2"></i>Tạo lại bảng (orders, cart)
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Demo Account Info -->
                        <div class="demo-box">
                            <h6 class="mb-2"><i class="bi bi-key me-2"></i>Tài khoản Demo</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Khách hàng:</small>
                                    <div><strong>Email:</strong> khachhang@gmail.com</div>
                                    <div><strong>Mật khẩu:</strong> admin123</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Nhân viên:</small>
                                    <div><strong>Username:</strong> admin</div>
                                    <div><strong>Mật khẩu:</strong> admin123</div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
                            </a>
                            <a href="<?= BASE_URL ?>/user/" class="btn btn-primary">
                                <i class="bi bi-shop me-2"></i>Trang chủ cửa hàng
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 text-muted">
                    <small>PharmaManager &copy; <?= date('Y') ?></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
