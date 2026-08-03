<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireEmployee();

$errors = [];
$formData = [
    'name' => '',
    'seat_count' => 4
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $formData['name'] = sanitize($_POST['name'] ?? '');
        $formData['seat_count'] = (int)($_POST['seat_count'] ?? 4);
        $table_id = (int)($_POST['table_id'] ?? 0);

        if (empty($formData['name'])) {
            $errors['name'] = 'Vui lòng nhập tên bàn';
        }
        if ($formData['seat_count'] <= 0) {
            $errors['seat_count'] = 'Số ghế không hợp lệ';
        }

        if (empty($errors)) {
            try {
                if ($_POST['action'] === 'add') {
                    $sql = "INSERT INTO tables (name, capacity, status, created_at) VALUES (?, ?, 'free', NOW())";
                    executeQuery($pdo, $sql, [$formData['name'], $formData['seat_count']]);
                    setMessage('success', 'Thêm bàn mới thành công!');
                } else {
                    $sql = "UPDATE tables SET name = ?, capacity = ? WHERE id = ?";
                    executeQuery($pdo, $sql, [$formData['name'], $formData['seat_count'], $table_id]);
                    setMessage('success', 'Cập nhật bàn thành công!');
                }
                redirect('/employee/tables/manage.php');
            } catch (PDOException $e) {
                $errors['general'] = 'Lỗi database: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all tables
try {
    $sql = "SELECT * FROM tables ORDER BY CAST(SUBSTRING(name, 5) AS UNSIGNED) ASC, name ASC";
    $tables = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    $tables = [];
}

$pageTitle = 'Quản lý bàn';
$additionalCSS = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<style>
    .mgmt-card { background: white; border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); margin-bottom: 1.5rem; }
    .mgmt-card-header { background: #fff; border-bottom: 1px solid #f8f9fa; padding: 1.25rem; border-radius: 12px 12px 0 0; }
    .mgmt-card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 0.5rem; }
    .mgmt-card-body { padding: 1.5rem; }
    
    .form-section-title { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
    .form-label { font-weight: 600; color: #475569; font-size: 0.85rem; }
    .btn-submit-mgmt { background: #2ec4b6; color: white; border: none; font-weight: 700; padding: 0.75rem; border-radius: 8px; transition: all 0.2s; }
    .btn-submit-mgmt:hover { background: #26a69a; color: white; transform: translateY(-1px); }
    
    .dataTables_wrapper .dt-buttons { margin-bottom: 1rem; }
    .dt-button { padding: 0.4rem 0.8rem !important; font-size: 0.75rem !important; border-radius: 6px !important; font-weight: 600 !important; }
    
    .status-badge { padding: 0.35em 0.65em; font-size: 0.75em; font-weight: 700; border-radius: 0.375rem; }
    .status-free { background: #d1fae5; color: #065f46; }
    .status-busy { background: #fee2e2; color: #991b1b; }
    
    .table-actions .btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid #e2e8f0; background: white; color: #64748b; }
    .table-actions .btn:hover { background: #f8fafc; color: #0d6efd; border-color: #0d6efd; }
</style>
';

require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Column: Danh sách các bàn -->
        <div class="col-lg-12">
            <div class="mgmt-card">
                <div class="mgmt-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mgmt-card-title"><i class="bi bi-grid-3x3-gap text-info me-2"></i>Danh sách các bàn</h5>
                    <a href="<?= BASE_URL ?>/employee/tables/index.php" class="small text-decoration-none text-muted fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại
                    </a>
                </div>
                <div class="mgmt-card-body">
                    <div class="table-responsive">
                        <table id="mgmtTables" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="80">MÃ BÀN</th>
                                    <th>TÊN BÀN</th>
                                    <th>SỨC CHỨA</th>
                                    <th>TRẠNG THÁI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $t): ?>
                                <tr>
                                    <td class="text-muted small">#<?= $t['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($t['name']) ?></td>
                                    <td class="text-muted small"><i class="bi bi-people me-1"></i> <?= $t['capacity'] ?> ghế</td>
                                    <td>
                                        <?php if($t['status'] === 'free'): ?>
                                            <span class="status-badge status-free">Trống</span>
                                        <?php elseif($t['status'] === 'busy' || $t['status'] === 'occupied'): ?>
                                            <span class="status-badge status-busy">Đang sử dụng</span>
                                        <?php elseif($t['status'] === 'booked' || $t['status'] === 'reserved'): ?>
                                            <span class="status-badge" style="background:#dbeafe; color:#1e40af;">Đã đặt</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background:#f1f5f9; color:#475569;"><?= htmlspecialchars($t['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$additionalJS = '
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $("#mgmtTables").DataTable({
        dom: "<\"d-flex justify-content-between align-items-center mb-3\"Bf>rt<\"d-flex justify-content-between align-items-center mt-3\"ip>",
        buttons: [
            { extend: "copy", className: "btn btn-sm btn-primary" },
            { extend: "csv", className: "btn btn-sm btn-info text-dark" },
            { extend: "excel", className: "btn btn-sm btn-success" },
            { extend: "pdf", className: "btn btn-sm btn-danger" },
            { extend: "print", className: "btn btn-sm btn-dark" }
        ],
        language: {
            search: "Tìm kiếm:",
            info: "Hiển thị _START_ - _END_ / _TOTAL_",
            paginate: { previous: "‹", next: "›" }
        },
        order: [[1, "asc"]]
    });
});
</script>
';
require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; 
?>
