<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

// Auto migration for bookings table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        table_id INT NULL,
        booking_date DATE NOT NULL,
        booking_time TIME NOT NULL,
        guests INT DEFAULT 2,
        note TEXT,
        status ENUM('pending', 'confirmed', 'using', 'cancelled', 'completed') DEFAULT 'pending',
        created_by_role ENUM('admin', 'employee') DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ensure 'using' is in the status enum
    $pdo->exec("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'using', 'cancelled', 'completed') DEFAULT 'pending'");

    // Check if created_by_role column exists, if not add it
    $checkColumn = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'created_by_role'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN created_by_role ENUM('admin', 'employee', 'customer') DEFAULT 'admin' AFTER status");
    } else {
        // Ensure 'customer' is in the enum
        $pdo->exec("ALTER TABLE bookings MODIFY COLUMN created_by_role ENUM('admin', 'employee', 'customer') DEFAULT 'admin'");
    }

    // Add customer_id column if not exists
    $checkCustomerId = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'customer_id'")->fetch();
    if (!$checkCustomerId) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN customer_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE bookings ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL");
    }
} catch (Exception $e) { }

// Handle Status Update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $status_map = [
        'confirm' => 'confirmed', 
        'checkin' => 'using',
        'cancel' => 'cancelled', 
        'complete' => 'completed'
    ];
    
    if (isset($status_map[$action])) {
        try {
            $pdo->beginTransaction();
            $booking = fetchOne($pdo, "SELECT table_id FROM bookings WHERE id = ?", [$id]);
            $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$status_map[$action], $id]);
            
            if ($booking && $booking['table_id']) {
                if ($action === 'confirm') {
                    $pdo->prepare("UPDATE tables SET status = 'booked' WHERE id = ?")->execute([$booking['table_id']]);
                } elseif ($action === 'checkin') {
                    $pdo->prepare("UPDATE tables SET status = 'busy' WHERE id = ?")->execute([$booking['table_id']]);
                } elseif ($action === 'cancel' || $action === 'complete') {
                    $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ? AND status IN ('booked', 'busy')")->execute([$booking['table_id']]);
                }
            }
            $pdo->commit();
            setMessage('success', 'Đã cập nhật trạng thái đặt bàn!');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
    }
    header("Location: index.php");
    exit;
}

// Handle Delete Booking
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        $booking = fetchOne($pdo, "SELECT table_id, status FROM bookings WHERE id = ?", [$id]);
        if ($booking) {
            $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
            if ($booking['table_id'] && ($booking['status'] === 'pending' || $booking['status'] === 'confirmed' || $booking['status'] === 'using')) {
                $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ? AND status IN ('booked', 'busy')")->execute([$booking['table_id']]);
            }
        }
        $pdo->commit();
        setMessage('success', 'Đã xóa đặt bàn!');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setMessage('danger', 'Lỗi: ' . $e->getMessage());
    }
    header("Location: index.php");
    exit;
}


// Get stats
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$stats = [
    'today' => fetchOne($pdo, "SELECT COUNT(*) as count FROM bookings WHERE booking_date = ?", [$today])['count'],
    'tomorrow' => fetchOne($pdo, "SELECT COUNT(*) as count FROM bookings WHERE booking_date = ?", [$tomorrow])['count'],
    'total' => fetchOne($pdo, "SELECT COUNT(*) as count FROM bookings")['count']
];

// Fetch Bookings
$current_filter = $_GET['filter'] ?? 'all';
$sql = "SELECT b.*, t.name as table_name FROM bookings b LEFT JOIN tables t ON b.table_id = t.id WHERE 1=1";
$params = [];

if ($current_filter === 'today') {
    $sql .= " AND b.booking_date = ?";
    $params[] = $today;
} elseif ($current_filter === 'tomorrow') {
    $sql .= " AND b.booking_date = ?";
    $params[] = $tomorrow;
}
$sql .= " ORDER BY b.booking_date ASC, b.booking_time ASC";
$bookings = fetchAll($pdo, $sql, $params);

$tables = fetchAll($pdo, "SELECT * FROM tables ORDER BY name");

$pageTitle = 'Quản lý đặt bàn';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="main-content-pos">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h3 class="fw-bold mb-0">Đặt bàn</h3>
        </div>
    </div>

     <!-- Hiển thị thông báo -->
    <?php $msg = getMessage(); if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show rounded-12 shadow-sm border-0 mb-4" role="alert">
            <i class="bi bi-<?= $msg['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($msg['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Bar -->
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <a href="?filter=today" class="text-decoration-none">
                <div class="booking-stat-card <?= $current_filter === 'today' ? 'active-filter' : '' ?> shadow-sm">
                    <div class="stat-icon bg-blue-pos"><i class="bi bi-calendar-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Hôm nay</div>
                        <div class="stat-value"><?= $stats['today'] ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="?filter=tomorrow" class="text-decoration-none">
                <div class="booking-stat-card <?= $current_filter === 'tomorrow' ? 'active-filter' : '' ?> shadow-sm">
                    <div class="stat-icon bg-gray-light-pos"><i class="bi bi-calendar-plus"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Ngày mai</div>
                        <div class="stat-value"><?= $stats['tomorrow'] ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="?filter=all" class="text-decoration-none">
                <div class="booking-stat-card <?= $current_filter === 'all' ? 'active-filter' : '' ?> shadow-sm">
                    <div class="stat-icon bg-gray-light-pos"><i class="bi bi-list-task"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Tổng đặt bàn</div>
                        <div class="stat-value"><?= $stats['total'] ?></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <!-- Empty State -->
        <div class="booking-empty-container shadow-sm border-0">
            <div class="empty-content text-center py-5">
                <div class="empty-icon-box mb-3">
                    <i class="bi bi-calendar-x fs-1 text-muted opacity-50"></i>
                </div>
                <h5 class="fw-bold mb-1">Không có lịch đặt bàn</h5>
                <p class="text-muted small mb-4">Rất tiếc, hiện tại không có lượt đặt bàn nào cho mục này.</p>
            </div>
        </div>
    <?php else: ?>
        <!-- Grid View -->
        <div class="row g-4">
            <?php foreach ($bookings as $b): 
                $status_theme = [
                    'pending' => 'warning',
                    'confirmed' => 'primary',
                    'using' => 'info',
                    'cancelled' => 'danger',
                    'completed' => 'success'
                ];
                $status_label = [
                    'pending' => 'Chờ xác nhận',
                    'confirmed' => 'Đã xác nhận',
                    'using' => 'Đang sử dụng',
                    'cancelled' => 'Đã hủy',
                    'completed' => 'Đã hoàn tất'
                ];
            ?>
                <div class="col-xl-4 col-md-6">
                    <div class="booking-card shadow-sm border-0">
                        <div class="card-header border-0 bg-white d-flex justify-content-between align-items-center pt-4 px-4 pb-0">
                            <span class="badge text-<?= $status_theme[$b['status']] ?> bg-<?= $status_theme[$b['status']] ?> bg-opacity-10 py-2 px-3 rounded-pill">
                                <?= $status_label[$b['status']] ?>
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-bold me-2">
                                    <?php if ($b['created_by_role'] === 'customer'): ?>
                                        <span class="badge bg-info text-white">Khách đặt</span>
                                    <?php endif; ?>
                                    #BK-<?= $b['id'] ?>
                                </span>
                                <a href="?action=delete&id=<?= $b['id'] ?>" class="btn btn-sm btn-icon rounded-circle bg-light border-0 shadow-sm" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa đặt bàn này?');">
                                    <i class="bi bi-trash-fill text-danger" style="font-size: 0.85rem;"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($b['customer_name']) ?></h5>
                            <p class="text-muted small mb-3"><i class="bi bi-telephone-fill me-2"></i><?= htmlspecialchars($b['customer_phone']) ?></p>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="info-mini">
                                        <div class="label">Ngày đặt</div>
                                        <div class="value"><?= date('d/m/Y', strtotime($b['booking_date'])) ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-mini">
                                        <div class="label">Giờ đặt</div>
                                        <div class="value"><?= date('H:i', strtotime($b['booking_time'])) ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-mini">
                                        <div class="label">Số khách</div>
                                        <div class="value"><?= $b['guests'] ?> người</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-mini text-primary">
                                        <div class="label">Số bàn</div>
                                        <div class="value fw-bold"><?= $b['table_name'] ?: 'Chưa xếp' ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($b['note']): ?>
                                <div class="alert alert-light border-0 mb-4 p-2 small text-muted italic">
                                    <i class="bi bi-chat-dots me-2"></i><?= htmlspecialchars($b['note']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <a href="?action=confirm&id=<?= $b['id'] ?>" class="btn btn-primary flex-grow-1 rounded-pill py-2-5 small fw-bold">Xác nhận</a>
                                    <a href="?action=checkin&id=<?= $b['id'] ?>" class="btn btn-info text-white flex-grow-1 rounded-pill py-2-5 small fw-bold">Nhận bàn</a>
                                    <a href="?action=cancel&id=<?= $b['id'] ?>" class="btn btn-outline-danger flex-grow-1 rounded-pill py-2-5 small fw-bold">Hủy</a>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <a href="?action=checkin&id=<?= $b['id'] ?>" class="btn btn-info text-white flex-grow-1 rounded-pill py-2-5 small fw-bold">Nhận bàn</a>
                                    <a href="?action=complete&id=<?= $b['id'] ?>" class="btn btn-success flex-grow-1 rounded-pill py-2-5 small fw-bold">Hoàn tất</a>
                                    <a href="?action=cancel&id=<?= $b['id'] ?>" class="btn btn-outline-danger flex-grow-1 rounded-pill py-2-5 small fw-bold">Hủy</a>
                                <?php elseif ($b['status'] === 'using'): ?>
                                    <a href="?action=complete&id=<?= $b['id'] ?>" class="btn btn-success flex-grow-1 rounded-pill py-2-5 small fw-bold">Hoàn tất</a>
                                    <a href="?action=cancel&id=<?= $b['id'] ?>" class="btn btn-outline-danger flex-grow-1 rounded-pill py-2-5 small fw-bold">Hủy</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .rounded-24 { border-radius: 24px; }
    .rounded-12 { border-radius: 12px; }
    .py-1-5 { padding-top: 0.375rem; padding-bottom: 0.375rem; }
    .p-2-5 { padding: 0.625rem 1rem; }
    .py-2-5 { padding-top: 0.625rem; padding-bottom: 0.625rem; }
    .x-small { font-size: 0.75rem; }
    
    .booking-stat-card {
        background: white; border-radius: 16px; padding: 24px 30px;
        display: flex; align-items: center; gap: 20px;
        border: 1px solid #f1f5f9; transition: all 0.3s ease;
        height: 100%;
    }
    .booking-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important; }
    .booking-stat-card.active-filter {
        border: 2px solid #3b82f6; background: #eff6ff !important;
    }
    .stat-icon {
        width: 56px; height: 56px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }
    .bg-blue-pos { background: #3b82f6; color: white; }
    .bg-gray-light-pos { background: #f1f5f9; color: #64748b; }
    .stat-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
    .stat-value { font-size: 1.75rem; font-weight: 800; color: #1e293b; }
    
    .booking-empty-container {
        background: white; border-radius: 24px;
        margin-top: 2rem;
    }
    .empty-icon-box {
        width: 80px; height: 80px; background: #f8fafc;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }

    .booking-card {
        background: white; border-radius: 20px; overflow: hidden;
    }
    .info-mini {
        background: #f8fafc; padding: 10px; border-radius: 12px;
    }
    .info-mini .label { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
    .info-mini .value { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
</style>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
