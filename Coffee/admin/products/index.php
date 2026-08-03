<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/config/cloudinary.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireAdmin();

// Auto-check and fix database schema if missing size columns
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('has_s', $checkCols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN has_s TINYINT(1) DEFAULT 0 AFTER is_available");
        $pdo->exec("ALTER TABLE products ADD COLUMN price_s DECIMAL(10,2) DEFAULT 0 AFTER has_s");
        $pdo->exec("ALTER TABLE products ADD COLUMN has_m TINYINT(1) DEFAULT 1 AFTER price_s");
        $pdo->exec("ALTER TABLE products ADD COLUMN price_m DECIMAL(10,2) DEFAULT 0 AFTER has_m");
        $pdo->exec("ALTER TABLE products ADD COLUMN has_l TINYINT(1) DEFAULT 0 AFTER price_m");
        $pdo->exec("ALTER TABLE products ADD COLUMN price_l DECIMAL(10,2) DEFAULT 0 AFTER has_l");
        $pdo->exec("ALTER TABLE products ADD COLUMN has_xl TINYINT(1) DEFAULT 0 AFTER price_l");
        $pdo->exec("ALTER TABLE products ADD COLUMN price_xl DECIMAL(10,2) DEFAULT 0 AFTER has_xl");
        $pdo->exec("ALTER TABLE products ADD COLUMN product_type VARCHAR(50) DEFAULT 'Đồ uống' AFTER category_id");
        
        // Sync existing 'price' to 'price_m'
        $pdo->exec("UPDATE products SET price_m = price WHERE price_m = 0");
    }
} catch (Exception $e) {
    error_log("Migration check failed: " . $e->getMessage());
}

// Handle Form Submission (Add/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $code = $_POST['code'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    
    // Fallback for missing category_id
    if (!$category_id) {
        $firstCat = fetchOne($pdo, "SELECT id FROM categories LIMIT 1");
        $category_id = $firstCat ? $firstCat['id'] : 1;
    }

    $product_type = $_POST['product_type'] ?? 'Đồ uống';
    $is_available = $_POST['is_available'] ?? 1;
    
    $has_s = isset($_POST['has_s']) ? 1 : 0;
    $price_s = $_POST['price_s'] ?: 0;
    $has_m = isset($_POST['has_m']) ? 1 : 0;
    $price_m = $_POST['price_m'] ?: 0;
    $has_l = isset($_POST['has_l']) ? 1 : 0;
    $price_l = $_POST['price_l'] ?: 0;
    
    // Auto-compute base price logic if needed or just zero it
    $price = $price_m ?: ($price_l ?: ($price_s ?: 0));

    $image_url = $_POST['current_image'] ?? '';

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadToCloudinary($_FILES['image']);
        if ($uploadResult['success']) {
            $image_url = $uploadResult['url'];
        }
    }

    try {
        if ($id) {
            // Update
            $sql = "UPDATE products SET 
                    name = ?, code = ?, category_id = ?, product_type = ?, is_available = ?,
                    has_s = ?, price_s = ?, has_m = ?, price_m = ?, has_l = ?, price_l = ?, price = ?,
                    image = ?
                    WHERE id = ?";
            executeQuery($pdo, $sql, [$name, $code, $category_id, $product_type, $is_available, $has_s, $price_s, $has_m, $price_m, $has_l, $price_l, $price, $image_url, $id]);
            setMessage('success', 'Cập nhật món thành công!');
        } else {
            // Create
            $sql = "INSERT INTO products (name, code, category_id, product_type, is_available, has_s, price_s, has_m, price_m, has_l, price_l, supplier_id, price, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
            executeQuery($pdo, $sql, [$name, $code, $category_id, $product_type, $is_available, $has_s, $price_s, $has_m, $price_m, $has_l, $price_l, $price, $image_url]);
            setMessage('success', 'Thêm món mới thành công!');
        }
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        setMessage('danger', 'Lỗi: ' . $e->getMessage());
    }
}

// Fetch Categories for select
$categories = fetchAll($pdo, "SELECT * FROM categories ORDER BY name ASC");

// Fetch Products for list
$products = fetchAll($pdo, "SELECT p.*, c.name as category_name 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            ORDER BY p.id DESC");

$pageTitle = 'Quản lý sản phẩm';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    :root {
        --premium-blue: #0d6efd;
        --premium-light-blue: #e7f1ff;
        --premium-dark: #1e293b;
        --premium-gray: #f8fafc;
        --premium-border: #e2e8f0;
    }

    .premium-page-container {
        padding: 1.5rem;
        background: #f1f5f9;
        min-height: calc(100vh - 100px);
    }

    .premium-card {
        background: white;
        border: none;
        border-radius: 1.25rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        height: 100%;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .premium-card-header {
        padding: 1.25rem 1.5rem;
        background: white;
        border-bottom: 1px solid var(--premium-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .premium-card-title {
        font-weight: 700;
        color: var(--premium-dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    #productsTable thead th {
        background: var(--premium-gray);
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem;
        border: none;
    }

    #productsTable tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--premium-border);
    }

    .product-img-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: var(--premium-light-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid var(--premium-border);
    }

    .product-img-preview-list {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .size-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-weight: 600;
        margin-right: 4px;
    }
    .badge-s { background: #fee2e2; color: #ef4444; }
    .badge-m { background: #fef9c3; color: #a16207; }
    .badge-l { background: #dcfce7; color: #15803d; }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        border: 1px solid var(--premium-border);
        background: white;
        color: #64748b;
    }

    .action-btn:hover {
        background: var(--premium-light-blue);
        color: var(--premium-blue);
        border-color: var(--premium-blue);
    }

    .action-btn-danger:hover {
        background: #fee2e2;
        color: #ef4444;
        border-color: #ef4444;
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
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    .size-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
        padding: 0.5rem;
        background: var(--premium-gray);
        border-radius: 0.75rem;
    }

    .size-label {
        min-width: 60px;
        font-weight: 700;
        color: var(--premium-dark);
    }

    .form-check-input-premium {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
    }

    .btn-save-premium {
        background: var(--premium-blue);
        color: white;
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem;
        font-weight: 600;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }

    .btn-save-premium:hover {
        background: #0b5ed7;
        transform: translateY(-2px);
    }

    .btn-reset-premium {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid var(--premium-border);
        border-radius: 0.75rem;
        padding: 0.75rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-reset-premium:hover {
        background: #e2e8f0;
    }

    .image-upload-container {
        position: relative;
        width: 100%;
        height: 150px;
        border: 2px dashed var(--premium-border);
        border-radius: 1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: var(--premium-gray);
        overflow: hidden;
        transition: all 0.2s;
    }

    .image-upload-container:hover {
        border-color: var(--premium-blue);
    }

    #imagePreview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
    }

    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
    }
</style>

<div class="premium-page-container">
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

    <div class="row g-4">
        <!-- Product List Column -->
        <div class="col-lg-7">
            <div class="premium-card">
                <div class="premium-card-header">
                    <h5 class="premium-card-title">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                        </div>
                        Danh sách món
                    </h5>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="resetForm()">
                        <i class="bi bi-plus-lg me-1"></i> Thêm mới
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="productsTable" class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Hình ảnh</th>
                                    <th>Tên món</th>
                                    <th class="text-primary">Giá</th>
                                    <th>Size</th>
                                    <th class="text-end pe-4">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <tr onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)" style="cursor: pointer;">
                                    <td class="ps-4">
                                        <div class="product-img-wrapper">
                                            <?php if ($p['image']): ?>
                                                <img src="<?= htmlspecialchars($p['image']) ?>" class="product-img-preview-list">
                                            <?php else: ?>
                                                <i class="bi bi-cup-hot text-primary opacity-50"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($p['code']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-danger"><?= number_format($p['price'] ?: ($p['price_m'] ?: 0), 0, ',', '.') ?>đ</div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if (!empty($p['has_s'])): ?><span class="size-badge badge-s">S</span><?php endif; ?>
                                            <?php if (!empty($p['has_m'])): ?><span class="size-badge badge-m">M</span><?php endif; ?>
                                            <?php if (!empty($p['has_l'])): ?><span class="size-badge badge-l">L</span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4" onclick="event.stopPropagation()">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="#" class="action-btn" onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)" title="Sửa">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $p['id'] ?>" class="action-btn action-btn-danger" 
                                               onclick="return confirm('Bạn có chắc muốn xóa món này?')" title="Xóa">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                        Chi tiết sản phẩm
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST" id="productForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="prod_id">
                        <input type="hidden" name="current_image" id="current_image">
                        
                        <div class="mb-4">
                            <label class="form-premium-label">Hình ảnh</label>
                            <label class="image-upload-container" for="imageInput">
                                <img id="imagePreview" src="" alt="Preview">
                                <div class="upload-placeholder" id="uploadPlaceholder">
                                    <i class="bi bi-camera-fill fs-2"></i>
                                    <span class="small">Chọn ảnh tải lên</span>
                                </div>
                                <input type="file" id="imageInput" name="image" hidden accept="image/*" onchange="previewImage(this)">
                            </label>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label for="prod_name" class="form-premium-label">Tên món</label>
                                <input type="text" class="form-control form-control-premium" id="prod_name" name="name" placeholder="Ví dụ: Cà phê sữa đá" required>
                            </div>
                            <div class="col-md-5">
                                <label for="prod_code" class="form-premium-label">Mã món</label>
                                <input type="text" class="form-control form-control-premium" id="prod_code" name="code" placeholder="CF001" required>
                            </div>
                        </div>

                        <label class="form-premium-label mb-2">Giá theo kích thước (VNĐ)</label>

                        <div class="size-row">
                            <input class="form-check-input form-check-input-premium" type="checkbox" name="has_l" id="has_l" value="1">
                            <span class="size-label">Size L</span>
                            <input type="number" class="form-control form-control-premium" name="price_l" id="price_l" placeholder="0">
                        </div>
                        <div class="size-row">
                            <input class="form-check-input form-check-input-premium" type="checkbox" name="has_m" id="has_m" value="1">
                            <span class="size-label">Size M</span>
                            <input type="number" class="form-control form-control-premium" name="price_m" id="price_m" placeholder="0">
                        </div>
                        <div class="size-row">
                            <input class="form-check-input form-check-input-premium" type="checkbox" name="has_s" id="has_s" value="1">
                            <span class="size-label">Size S</span>
                            <input type="number" class="form-control form-control-premium" name="price_s" id="price_s" placeholder="0">
                        </div>

                        <div class="row mt-4 g-3">
                            <div class="col-12">
                                <label for="is_available" class="form-premium-label">Trạng thái</label>
                                <select class="form-select form-control-premium" id="is_available" name="is_available">
                                    <option value="1">Đang bán</option>
                                    <option value="0">Hết hàng</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-5">
                            <button type="button" class="btn btn-reset-premium" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button type="submit" name="save_product" class="btn btn-save-premium" id="saveButton">
                                <i class="bi bi-plus-lg" id="buttonIcon"></i> 
                                <span id="buttonText">Thêm mới</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#productsTable').DataTable({
        language: { 
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json',
            search: "Tìm kiếm:",
            searchPlaceholder: "Nhập tên món, mã món..."
        },
        pageLength: 10,
        dom: '<"p-3 d-flex justify-content-between"f>t<"p-3 d-flex justify-content-between"ip>',
        order: [[1, 'asc']]
    });
});

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result).show();
            $('#uploadPlaceholder').hide();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function editProduct(product) {
    document.getElementById('prod_id').value = product.id || '';
    document.getElementById('prod_name').value = product.name || '';
    document.getElementById('prod_code').value = product.code || '';
    document.getElementById('is_available').value = product.is_available ?? 1;
    document.getElementById('current_image').value = product.image || '';

    if (product.image) {
        $('#imagePreview').attr('src', product.image).show();
        $('#uploadPlaceholder').hide();
    } else {
        $('#imagePreview').hide();
        $('#uploadPlaceholder').show();
    }

    document.getElementById('has_s').checked = (product.has_s == 1);
    document.getElementById('price_s').value = Math.floor(product.price_s) || 0;
    document.getElementById('has_m').checked = (product.has_m == 1);
    document.getElementById('price_m').value = Math.floor(product.price_m) || Math.floor(product.price) || 0;
    document.getElementById('has_l').checked = (product.has_l == 1);
    document.getElementById('price_l').value = Math.floor(product.price_l) || 0;


    document.getElementById('buttonText').innerText = 'Cập nhật';
    document.getElementById('buttonIcon').className = 'bi bi-check-lg';
    
    if (window.innerWidth < 992) {
        document.getElementById('productForm').scrollIntoView({ behavior: 'smooth' });
    }
}

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('prod_id').value = '';
    document.getElementById('current_image').value = '';
    $('#imagePreview').hide();
    $('#uploadPlaceholder').show();
    document.getElementById('buttonText').innerText = 'Thêm mới';
    document.getElementById('buttonIcon').className = 'bi bi-plus-lg';
}
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
