<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/customer_header.php';

// Auto migration for bookings table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        table_id INT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        guests INT DEFAULT 2,
        note TEXT,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        created_by_role ENUM('admin', 'employee', 'customer') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ensure 'using' is in the status enum
    $pdo->exec("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'using', 'cancelled', 'completed') DEFAULT 'pending'");

    // Check if created_by_role column exists, if not add it
    $checkColumn = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'created_by_role'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN created_by_role ENUM('admin', 'employee', 'customer') DEFAULT 'customer' AFTER status");
    } else {
        // Ensure 'customer' is in the enum
        $pdo->exec("ALTER TABLE bookings MODIFY COLUMN created_by_role ENUM('admin', 'employee', 'customer') DEFAULT 'customer'");
    }

    // Add customer_id column if not exists
    $checkCustomerId = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'customer_id'")->fetch();
    if (!$checkCustomerId) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN customer_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE bookings ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL");
    }

    // CRITICAL: Ensure 'booked' is supported in tables status
    $pdo->exec("ALTER TABLE tables MODIFY COLUMN status ENUM('free', 'booked', 'busy') DEFAULT 'free'");
} catch (Exception $e) { }

$success = false;
$error = '';
$fromCart = isset($_GET['from_cart']) || isset($_POST['from_cart']);

// Fetch all tables
$tables = fetchAll($pdo, "SELECT * FROM tables ORDER BY name ASC");

// Check if cart is empty for the current customer
$cartCount = 0;
if (isset($_SESSION['customer_id'])) {
    $cartStmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE customer_id = ?");
    $cartStmt->execute([$_SESSION['customer_id']]);
    $cartCount = $cartStmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_table'])) {
    $name = sanitize($_POST['customer_name']);
    $phone = sanitize($_POST['customer_phone']);
    $date = sanitize($_POST['booking_date']);
    $time = sanitize($_POST['booking_time']);
    $guests = (int)$_POST['guests'];
    $note = sanitize($_POST['note']);
    $tableId = isset($_POST['table_id']) ? (int)$_POST['table_id'] : null;
    $customerId = $_SESSION['customer_id'] ?? null;

    // 1. Check if cart is empty
    if ($cartCount == 0) {
        $error = "Vui lòng thêm món vào giỏ hàng trước khi đặt bàn!";
    } 
    // 2. Validate time (cannot be in the past)
    else {
        $bookingDateTime = strtotime("$date $time");
        $now = time();
        if ($bookingDateTime < $now) {
            $error = "Thời gian đặt bàn không hợp lệ (không thể đặt thời gian trong quá khứ)!";
        }
    }

    if (empty($error)) {
        try {
            $sql = "INSERT INTO bookings (customer_id, customer_name, customer_phone, table_id, booking_date, booking_time, guests, note, created_by_role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'customer')";
            $pdo->prepare($sql)->execute([$customerId, $name, $phone, $tableId, $date, $time, $guests, $note]);
            $bookingId = $pdo->lastInsertId();
            
            // Update table status to 'booked' if a table was selected
            if ($tableId) {
                $pdo->prepare("UPDATE tables SET status = 'booked' WHERE id = ?")->execute([$tableId]);
            }

            $success = true;

            if ($fromCart) {
                echo "<script>window.location.href = 'checkout.php?booking_id=$bookingId';</script>";
                exit;
            }
        } catch (Exception $e) {
            $error = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// Pre-fill data if logged in
$preFillName = $_SESSION['customer_name'] ?? '';
$preFillPhone = '';

if (isset($_SESSION['customer_id'])) {
    $cust = fetchOne($pdo, "SELECT phone FROM customers WHERE id = ?", [$_SESSION['customer_id']]);
    if ($cust) $preFillPhone = $cust['phone'];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($success): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center mb-4">
                    <div class="mb-4">
                        <i class="bi bi-calendar-check text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-3">Đặt Bàn Thành Công!</h2>
                    <p class="text-muted mb-4">
                        Yêu cầu đặt bàn của bạn đã được gửi đi. Chúng tôi sẽ liên hệ sớm nhất để xác nhận.<br>
                        Cảm ơn bạn đã tin tưởng Coffee Manager.
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-primary rounded-pill px-4">Về trang chủ</a>
                        <a href="menu.php" class="btn btn-outline-primary rounded-pill px-4">Xem thực đơn</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="p-5">
                        <h3 class="fw-bold mb-4 text-center">Thông Tin Đặt Bàn</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-3"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">HỌ TÊN</label>
                                    <input type="text" name="customer_name" class="form-control rounded-3 p-3" required value="<?= htmlspecialchars($preFillName) ?>" placeholder="Nguyễn Văn A">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">SỐ ĐIỆN THOẠI</label>
                                    <input type="tel" name="customer_phone" class="form-control rounded-3 p-3" required value="<?= htmlspecialchars($preFillPhone) ?>" placeholder="0123...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">NGÀY ĐẶT</label>
                                    <input type="date" name="booking_date" class="form-control rounded-3 p-3" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">GIỜ ĐẶT</label>
                                    <input type="time" name="booking_time" class="form-control rounded-3 p-3" required value="19:00">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">SỐ KHÁCH</label>
                                    <input type="number" name="guests" class="form-control rounded-3 p-3" required value="2" min="1">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">CHỌN BÀN</label>
                                    <div class="table-grid">
                                        <?php foreach ($tables as $t): 
                                            $isBusy = $t['status'] !== 'free';
                                            $statusText = ($t['status'] === 'busy' || $t['status'] === 'occupied') ? 'Đang sử dụng' : (($t['status'] === 'booked' || $t['status'] === 'reserved') ? 'Đã đặt' : 'Trống');
                                        ?>
                                            <div class="table-item <?= $isBusy ? 'busy' : '' ?>" 
                                                 onclick="<?= $isBusy ? "showBusyMsg('$statusText')" : "selectTable(".$t['id'].", this)" ?>">
                                                <div class="table-name"><?= htmlspecialchars($t['name']) ?></div>
                                                <div class="table-cap text-muted small"><?= $t['capacity'] ?> chỗ</div>
                                                <div class="table-status mt-1"><?= $statusText ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="table_id" id="selected_table_id" value="">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">GHI CHÚ</label>
                                    <textarea name="note" class="form-control rounded-3 p-3" rows="3" placeholder="Yêu cầu đặc biệt..."></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <?php if ($cartCount == 0): ?>
                                        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-3">
                                            <i class="bi bi-cart-x me-2"></i>
                                            Bạn cần chọn món vào giỏ hàng trước khi có thể đặt bàn.
                                            <a href="menu.php" class="alert-link ms-2">Đến Menu ngay</a>
                                        </div>
                                        <button type="button" class="btn btn-secondary w-100 rounded-pill py-3 fw-bold opacity-50" disabled>
                                            <i class="bi bi-lock-fill me-2"></i>Vui lòng đặt món trước
                                        </button>
                                    <?php elseif ($fromCart): ?>
                                        <input type="hidden" name="from_cart" value="1">
                                        <button type="submit" name="book_table" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                                            XÁC NHẬN ĐẶT BÀN & THANH TOÁN (<?= $cartCount ?> món)
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="book_table" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                                            XÁC NHẬN ĐẶT BÀN (<?= $cartCount ?> món)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const dateInput = document.querySelector('input[name="booking_date"]');
        const timeInput = document.querySelector('input[name="booking_time"]');
        const tableInput = document.getElementById('selected_table_id');
        
        if (!tableInput || !tableInput.value) {
            alert('Vui lòng chọn bàn!');
            e.preventDefault();
            return;
        }

        // Validate future time
        const bookingDateTime = new Date(dateInput.value + 'T' + timeInput.value);
        const now = new Date();

        if (bookingDateTime < now) {
            alert('Thời gian đặt bàn không hợp lệ! Vui lòng chọn thời gian trong tương lai.');
            e.preventDefault();
            return;
        }
    });
});
</script>

<style>
    .form-control:focus {
        border-color: #d35400;
        box-shadow: 0 0 0 0.25rem rgba(211, 84, 0, 0.1);
    }

    .table-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }

    .table-item {
        background: #fff;
        border: 2px solid #eee;
        border-radius: 15px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .table-item:hover:not(.busy) {
        border-color: #e67e22;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .table-item.selected {
        border-color: #e67e22;
        background-color: #fef5e7;
    }

    .table-item.selected::after {
        content: '✓';
        position: absolute;
        top: 5px;
        right: 10px;
        color: #e67e22;
        font-weight: bold;
    }

    .table-item.busy {
        background-color: #f8f9fa;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .table-item.busy .table-status {
        color: #e74c3c;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .table-item:not(.busy) .table-status {
        color: #27ae60;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .table-name {
        font-weight: 700;
        color: #2c3e50;
    }
</style>

<script>
function selectTable(id, element) {
    // Deselect others
    document.querySelectorAll('.table-item').forEach(el => el.classList.remove('selected'));
    // Select this
    element.classList.add('selected');
    // Set input value
    document.getElementById('selected_table_id').value = id;
}

function showBusyMsg(status) {
    alert("Bàn này " + status + ". Vui lòng chọn bàn khác.");
}
</script>

<?php require_once 'includes/customer_footer.php'; ?>
