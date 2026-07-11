<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_once dirname(dirname(__DIR__)) . '/config/cloudinary.php';

requireAdmin();

// Auto-check and fix database schema if missing employee columns
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $checkCols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) AFTER full_name");
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email");
        $pdo->exec("ALTER TABLE users ADD COLUMN shift VARCHAR(50) DEFAULT 'Ca sáng' AFTER phone");
        $pdo->exec("ALTER TABLE users ADD COLUMN rating DECIMAL(2,1) DEFAULT 5.0 AFTER shift");
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) AFTER rating");
        $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'Đang làm việc' AFTER is_active");
    }
} catch (Exception $e) {
    error_log("User Migration check failed: " . $e->getMessage());
}

// Handle Form Submission (Add/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'employee';
    $shift = $_POST['shift'] ?? 'Ca sáng';
    $status = $_POST['status'] ?? 'Đang làm việc';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    
    $avatar_url = $_POST['current_avatar'] ?? '';

    // Handle Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadToCloudinary($_FILES['avatar']);
        if ($uploadResult['success']) {
            $avatar_url = $uploadResult['url'];
        }
    }

    try {
        if ($id) {
            // Update
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, shift=?, status=?, is_active=?, avatar=?, password=? WHERE id=?";
                $params = [$full_name, $email, $phone, $role, $shift, $status, $is_active, $avatar_url, $hashed, $id];
            } else {
                $sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, shift=?, status=?, is_active=?, avatar=? WHERE id=?";
                $params = [$full_name, $email, $phone, $role, $shift, $status, $is_active, $avatar_url, $id];
            }
            executeQuery($pdo, $sql, $params);
            setMessage('success', "Cập nhật nhân viên '$full_name' thành công!");
        } else {
            // Add
            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Tên đăng nhập '$username' đã tồn tại. Vui lòng chọn tên khác.");
            }

            $hashed = password_hash($password ?: '123456', PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, full_name, email, phone, role, shift, status, is_active, avatar, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$username, $full_name, $email, $phone, $role, $shift, $status, $is_active, $avatar_url, $hashed];
            executeQuery($pdo, $sql, $params);
            setMessage('success', "Thêm nhân viên '$full_name' thành công!");
        }
    } catch (Exception $e) {
        setMessage('danger', 'Lỗi: ' . $e->getMessage());
    }
    // Refresh to stop resubmission
    header("Location: index.php");
    exit;
}

try {
    $sql = "SELECT * FROM users WHERE role != 'admin' ORDER BY full_name ASC";
    $users = fetchAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("User List Error: " . $e->getMessage());
    $users = [];
}

$pageTitle = 'Quản lý nhân viên';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Header Thông báo -->
<div id="alertContainer" class="px-4 mt-4">
    <?php $msg = getMessage(); if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-<?= $msg['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
            <?= htmlspecialchars($msg['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>

<!-- Page Header -->
<style>
    :root {
        --premium-blue: #0ea5e9;
        --premium-light-blue: #f0f9ff;
        --premium-green: #10b981;
        --premium-light-green: #ecfdf5;
        --premium-red: #ef4444;
        --premium-light-red: #fef2f2;
        --premium-gray: #f8fafc;
        --premium-border: #e2e8f0;
        --premium-text: #1e293b;
        --premium-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .dashboard-container {
        padding: 1.5rem;
        background: #f1f5f9;
        min-height: calc(100vh - 70px);
    }

    .premium-card {
        background: white;
        border-radius: 1.5rem;
        border: none;
        box-shadow: var(--premium-shadow);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .premium-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--premium-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .premium-card-title {
        font-weight: 800;
        font-size: 1.25rem;
        color: var(--premium-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* User Card Grid */
    .user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .user-card {
        background: white;
        border: 1px solid var(--premium-border);
        border-radius: 1.25rem;
        padding: 1.25rem;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }

    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px -5px rgba(0,0,0,0.1);
        border-color: var(--premium-blue);
    }

    .user-card.active-item {
        border: 2px solid var(--premium-blue);
        background: var(--premium-light-blue);
    }

    .user-header-info {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .user-avatar-premium {
        width: 60px;
        height: 60px;
        border-radius: 1rem;
        object-fit: cover;
        background: #f1f5f9;
        border: 2px solid white;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .user-badge-role {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 0.5rem;
        text-transform: uppercase;
        margin-top: 0.25rem;
        display: inline-block;
    }

    .user-detail-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
        color: #64748b;
        font-size: 0.85rem;
    }

    .user-detail-row i {
        color: #94a3b8;
        width: 16px;
    }

    .status-pill {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 100px;
    }

    .form-premium-label {
        font-weight: 600;
        color: #475569;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .form-control-premium {
        border-radius: 0.75rem;
        padding: 0.625rem 1rem;
        border: 1px solid var(--premium-border);
        background: var(--premium-gray);
        transition: all 0.2s;
    }

    .form-control-premium:focus {
        background: white;
        border-color: var(--premium-blue);
        box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
    }

    .avatar-upload-container {
        width: 100px;
        height: 100px;
        border-radius: 1.5rem;
        border: 2px dashed var(--premium-border);
        overflow: hidden;
        position: relative;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        background: var(--premium-gray);
        transition: all 0.2s;
    }

    .avatar-upload-container:hover {
        border-color: var(--premium-blue);
        background: var(--premium-light-blue);
    }

    #avatarPreview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }

    .btn-save-premium {
        background: var(--premium-blue);
        color: white;
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        font-weight: 700;
        flex-grow: 1;
        transition: all 0.2s;
    }

    .btn-save-premium:hover {
        background: #0284c7;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .btn-reset-premium {
        background: white;
        border: 1px solid var(--premium-border);
        border-radius: 0.75rem;
        padding: 0.75rem;
        color: #64748b;
        transition: all 0.2s;
    }

    .btn-reset-premium:hover {
        background: #f1f5f9;
        color: var(--premium-red);
        border-color: var(--premium-red);
    }

    .rating-stars { color: #f59e0b; }
</style>

    <div class="row g-4 px-4">
        <!-- List Column -->
        <div class="col-lg-7">
            <div class="premium-card">
                <div class="premium-card-header">
                    <h5 class="premium-card-title">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        Danh sách nhân viên
                    </h5>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" onclick="resetForm()">
                        <i class="bi bi-person-plus me-1"></i> Thêm mới
                    </button>
                </div>
                <div class="overflow-auto" style="max-height: calc(100vh - 250px);">
                    <div class="user-grid" id="userGrid">
                        <?php foreach ($users as $u): ?>
                        <div class="user-card" id="user-card-<?= $u['id'] ?>" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-profile-avatar" style="width: 48px; height: 48px; font-size: 1.2rem; flex-shrink: 0;">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($u['full_name']) ?></h6>
                                            <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                        </div>
                                        <span class="status-pill <?= $u['status'] === 'Đang làm việc' ? 'bg-success text-white' : 'bg-warning text-dark' ?>" style="font-size: 0.6rem;">
                                            <?= htmlspecialchars($u['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    <i class="bi bi-clock me-1"></i> <?= htmlspecialchars($u['shift'] ?: 'Ca sáng') ?>
                                </div>
                                <div class="rating-stars small">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="fw-bold ms-1"><?= number_format($u['rating'], 1) ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    <i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($u['phone'] ?: '-') ?>
                                </div>
                                <a href="delete.php?id=<?= $u['id'] ?>" class="text-danger small" onclick="event.stopPropagation(); return confirm('Xóa nhân viên này?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Column -->
        <div class="col-lg-5">
            <div class="premium-card sticky-top" style="top: 2rem;">
                <div class="premium-card-header">
                    <h5 class="premium-card-title">
                        <div class="bg-success text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <span id="formTitle">Chi tiết nhân viên</span>
                    </h5>
                    <div id="idBadge" class="badge bg-light text-muted rounded-pill">Mới</div>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST" id="userForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="user_id">
                        <input type="hidden" name="current_avatar" id="current_avatar">
                        
                        <label class="image-upload-container avatar-upload-container" for="avatarInput">
                            <img id="avatarPreview" src="" style="display: none;">
                            <div id="avatarPlaceholder" class="text-center text-muted">
                                <i class="bi bi-camera-fill fs-3"></i><br>
                                <span class="small">Ảnh</span>
                            </div>
                            <input type="file" id="avatarInput" name="avatar" hidden accept="image/*" onchange="previewAvatar(this)">
                        </label>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6" id="usernameField">
                                <label for="u_username" class="form-premium-label">Tên đăng nhập</label>
                                <input type="text" class="form-control form-control-premium" id="u_username" name="username" placeholder="nguyenvanan" required autocomplete="off">
                                <small class="text-muted">Dùng để đăng nhập</small>
                            </div>
                            <div class="col-md-6">
                                <label for="u_full_name" class="form-premium-label">Họ và tên</label>
                                <input type="text" class="form-control form-control-premium" id="u_full_name" name="full_name" placeholder="Nguyễn Văn An" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="u_email" class="form-premium-label">Email</label>
                                <input type="email" class="form-control form-control-premium" id="u_email" name="email" placeholder="an@gmail.com">
                            </div>
                            <div class="col-md-6">
                                <label for="u_phone" class="form-premium-label">Số điện thoại</label>
                                <input type="text" class="form-control form-control-premium" id="u_phone" name="phone" placeholder="0123.456.789">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="u_role" class="form-premium-label">Vai trò</label>
                                <select class="form-select form-control-premium" id="u_role" name="role">
                                    <option value="employee">Nhân viên</option>
                                    <option value="admin">Quản lý</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="shiftField">
                                <label for="u_shift" class="form-premium-label">Ca làm việc</label>
                                <select class="form-select form-control-premium" id="u_shift" name="shift">
                                    <option value="Ca sáng">Ca sáng</option>
                                    <option value="Ca chiều">Ca chiều</option>
                                    <option value="Ca tối">Ca tối</option>
                                    <option value="Ca cả ngày">Ca cả ngày</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6" id="statusField">
                                <label for="u_status" class="form-premium-label">Trạng thái</label>
                                <select class="form-select form-control-premium" id="u_status" name="status">
                                    <option value="Đang làm việc">Đang làm việc</option>
                                    <option value="Tạm nghỉ">Tạm nghỉ</option>
                                    <option value="Nghỉ việc">Nghỉ việc</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-premium-label">Mật khẩu</label>
                                <input type="password" class="form-control form-control-premium" name="password" id="u_password" placeholder="••••••">
                                <small class="text-muted" id="pwDesc">Mặc định: 123456</small>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-5 ms-1">
                            <input class="form-check-input" type="checkbox" name="is_active" id="u_is_active" value="1" checked>
                            <label class="form-check-label fw-bold" for="u_is_active">Kích hoạt tài khoản</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-reset-premium" onclick="resetForm()" title="Làm mới form">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button type="submit" name="save_user" class="btn btn-save-premium" id="saveButton">
                                <i class="bi bi-save me-2"></i> 
                                <span id="buttonText">Thêm nhân viên</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#avatarPreview').attr('src', e.target.result).show();
            $('#avatarPlaceholder').hide();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function editUser(user) {
    // UI Feedback
    $('.user-card').removeClass('active-item');
    $('#user-card-' + user.id).addClass('active-item');
    
    // Fill Form
    document.getElementById('user_id').value = user.id;
    document.getElementById('u_username').value = user.username;
    document.getElementById('u_username').readOnly = true;
    document.getElementById('u_full_name').value = user.full_name;
    document.getElementById('u_email').value = user.email || '';
    document.getElementById('u_phone').value = user.phone || '';
    document.getElementById('u_role').value = user.role;
    document.getElementById('u_shift').value = user.shift || 'Ca sáng';
    document.getElementById('u_status').value = user.status || 'Đang làm việc';
    document.getElementById('u_is_active').checked = (user.is_active == 1);
    document.getElementById('current_avatar').value = user.avatar || '';
    document.getElementById('u_password').placeholder = 'Bỏ trống nếu không đổi';
    document.getElementById('pwDesc').textContent = 'Để trống để giữ nguyên mật khẩu cũ';

    if (user.avatar) {
        $('#avatarPreview').attr('src', user.avatar).show();
        $('#avatarPlaceholder').hide();
    } else {
        $('#avatarPreview').hide();
        $('#avatarPlaceholder').show();
    }

    // Header & Button Text
    document.getElementById('formTitle').textContent = 'Sửa nhân viên';
    document.getElementById('buttonText').textContent = 'Cập nhật nhân viên';
    document.getElementById('idBadge').textContent = 'ID: #' + user.id;
    document.getElementById('idBadge').className = 'badge bg-primary rounded-pill';

    toggleFieldsByRole(user.role);
}

function toggleFieldsByRole(role) {
    if (role === 'admin') {
        $('#shiftField').fadeOut();
        $('#statusField').fadeOut();
        // Clear values conceptually for admin
        $('#u_shift').val('Ca sáng');
        $('#u_status').val('Đang làm việc');
    } else {
        $('#shiftField').fadeIn();
        $('#statusField').fadeIn();
    }
}

// Add role change listener
$(document).ready(function() {
    $('#u_role').on('change', function() {
        toggleFieldsByRole($(this).val());
    });
});

function resetForm() {
    $('.user-card').removeClass('active-item');
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('u_username').readOnly = false;
    $('#avatarPreview').hide().attr('src', '');
    $('#avatarPlaceholder').show();
    document.getElementById('formTitle').textContent = 'Thêm nhân viên';
    document.getElementById('buttonText').textContent = 'Thêm nhân viên';
    document.getElementById('idBadge').textContent = 'Mới';
    document.getElementById('idBadge').className = 'badge bg-light text-muted rounded-pill';
    document.getElementById('u_password').placeholder = '••••••';
    document.getElementById('pwDesc').textContent = 'Mặc định: 123456';
    
    toggleFieldsByRole('employee');
}
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
