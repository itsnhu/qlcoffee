<?php

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

// Get customers with order statistics
try {
    $sql = "SELECT c.*,
                   COUNT(DISTINCT o.id) as total_orders,
                   COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM customers c
            LEFT JOIN orders o ON c.id = o.customer_id
            GROUP BY c.id
            ORDER BY c.created_at DESC";
    $customers = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Customer List Error: " . $e->getMessage());
    $customers = [];
    setMessage('danger', 'Có lỗi khi tải danh sách khách hàng.');
}

$pageTitle = 'Quản lý khách hàng';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Display message -->
<?php
$message = getMessage();
if ($message):
?>
    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
        <?php echo htmlspecialchars($message['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-person-badge text-primary"></i>
                    Quản lý khách hàng
                </h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Quản lý tài khoản khách hàng đã đăng ký trên hệ thống
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Tổng khách hàng</h6>
                        <h3 class="mb-0"><?php echo count($customers); ?></h3>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Đang hoạt động</h6>
                        <h3 class="mb-0"><?php echo count(array_filter($customers, fn($c) => $c['is_active'])); ?></h3>
                    </div>
                    <i class="bi bi-person-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-dark-50 mb-1">Đã vô hiệu</h6>
                        <h3 class="mb-0"><?php echo count(array_filter($customers, fn($c) => !$c['is_active'])); ?></h3>
                    </div>
                    <i class="bi bi-person-x fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Tổng doanh thu</h6>
                        <h3 class="mb-0"><?php echo formatCurrency(array_sum(array_column($customers, 'total_spent'))); ?></h3>
                    </div>
                    <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customers Table -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-table me-2"></i>
        <strong>Danh sách khách hàng</strong>
        <span class="badge bg-light text-dark ms-2"><?php echo count($customers); ?> khách hàng</span>
    </div>
    <div class="card-body">
        <?php if (empty($customers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3 text-muted">Chưa có khách hàng nào đăng ký</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="customersTable" class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th class="text-center">Đơn hàng</th>
                            <th class="text-end">Tổng chi tiêu</th>
                            <th>Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                <td>
                                    <i class="bi bi-person-circle me-2 text-primary"></i>
                                    <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($customer['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($customer['phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($customer['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $customer['total_orders']; ?></span>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo formatCurrency($customer['total_spent']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($customer['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Hoạt động
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Vô hiệu hóa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatDate($customer['created_at'], DATETIME_FORMAT); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>/admin/customers/view.php?id=<?php echo $customer['id']; ?>"
                                           class="btn btn-outline-info"
                                           title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/customers/edit.php?id=<?php echo $customer['id']; ?>"
                                           class="btn btn-outline-warning"
                                           title="Sửa">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/customers/toggle-status.php?id=<?php echo $customer['id']; ?>"
                                           class="btn btn-outline-<?php echo $customer['is_active'] ? 'danger' : 'success'; ?>"
                                           onclick="return confirm('Bạn có chắc chắn muốn <?php echo $customer['is_active'] ? 'vô hiệu hóa' : 'kích hoạt'; ?> tài khoản này?');"
                                           title="<?php echo $customer['is_active'] ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>">
                                            <i class="bi bi-<?php echo $customer['is_active'] ? 'lock' : 'unlock'; ?>"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#customersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
        columnDefs: [
            { orderable: false, targets: 8 }
        ]
    });
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
