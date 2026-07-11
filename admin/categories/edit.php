<?php



require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';


requireAdmin();

$errors = [];
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;


try {
    $sql = "SELECT * FROM categories WHERE id = ?";
    $category = fetchOne($pdo, $sql, [$categoryId]);

    if (!$category) {
        setMessage('danger', 'Không tìm thấy danh mục!');
        redirect('/admin/categories/index.php');
    }
} catch (PDOException $e) {
    error_log("Fetch Category Error: " . $e->getMessage());
    setMessage('danger', 'Có lỗi khi tải thông tin danh mục.');
    redirect('/admin/categories/index.php');
}

$formData = [
    'name' => $category['name']
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $formData['name'] = sanitize($_POST['name'] ?? '');

    
    if (empty($formData['name'])) {
        $errors['name'] = 'Vui lòng nhập tên danh mục';
    } elseif (strlen($formData['name']) < 2) {
        $errors['name'] = 'Tên danh mục phải có ít nhất 2 ký tự';
    }

    
    if (empty($errors['name'])) {
        try {
            $sql = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND id != ?";
            $result = fetchOne($pdo, $sql, [$formData['name'], $categoryId]);
            if ($result['count'] > 0) {
                $errors['name'] = 'Tên danh mục đã tồn tại';
            }
        } catch (PDOException $e) {
            error_log("Check Category Name Error: " . $e->getMessage());
            $errors['name'] = 'Có lỗi khi kiểm tra tên danh mục';
        }
    }

    
    if (empty($errors)) {
        try {
            
            $sql = "UPDATE categories SET name = ? WHERE id = ?";
            $params = [$formData['name'], $categoryId];

            executeQuery($pdo, $sql, $params);

            
            setMessage('success', 'Cập nhật danh mục thành công!');
            redirect('/admin/categories/index.php');
        } catch (PDOException $e) {
            error_log("Update Category Error: " . $e->getMessage());
            $errors['general'] = 'Có lỗi khi cập nhật danh mục. Vui lòng thử lại.';
        }
    }
}

$pageTitle = 'Chỉnh sửa danh mục';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>/admin/categories/index.php">Danh mục</a>
                </li>
                <li class="breadcrumb-item active">Chỉnh sửa</li>
            </ol>
        </nav>
        <h2 class="mb-2">
            <i class="bi bi-pencil-square text-warning"></i>
            Chỉnh sửa danh mục
        </h2>
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Cập nhật thông tin danh mục: <strong><?php echo htmlspecialchars($category['name']); ?></strong>
        </p>
    </div>
</div>

<!-- Display general error -->
<?php if (isset($errors['general'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($errors['general']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Edit Category Form -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-pencil-square me-2"></i>
                <strong>Thông tin danh mục</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="editCategoryForm">
                    <!-- Category Name -->
                    <div class="mb-4">
                        <label for="name" class="form-label">
                            Tên danh mục <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-tag-fill"></i>
                            </span>
                            <input type="text"
                                   class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                   id="name"
                                   name="name"
                                   value="<?php echo htmlspecialchars($formData['name']); ?>"
                                   placeholder="Ví dụ: Cà phê, Trà sữa, Sinh tố..."
                                   required
                                   autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errors['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">
                            Tên danh mục phải có ít nhất 2 ký tự và chưa tồn tại trong hệ thống.
                        </small>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo BASE_URL; ?>/admin/categories/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Quay lại
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle me-2"></i>
                            Cập nhật danh mục
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
