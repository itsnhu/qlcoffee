    </main>
    
    <footer class="footer-premium">
        <div class="container">
            <!-- Top Footer -->
            <div class="row g-4 mb-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="logo-circle" style="width: 55px; height: 55px; background-color: #fccaca; border: 2px solid #FFFFFF;">
                            <div class="logo-inner">
                                <i class="bi bi-cup-hot-fill" style="color: #2C1810; font-size: 1.8rem;"></i>
                            </div>
                        </div>
                        <span class="h2 fw-bold mb-0 font-heading text-white">TNT Coffee</span>
                    </div>
                    <p class="fs-5 mb-3" style="opacity: 0.9; font-weight: 500; color: #FFF;">
                        Chất lượng – Nguyên chất – Uy tín
                    </p>
                    <div class="d-flex gap-2 mt-2">
                        <a href="#" class="social-icon-footer" style="color: #1877F2; background: white;"><i class="bi bi-facebook fs-4"></i></a>
                        <a href="#" class="social-icon-footer" style="background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); color: white;"><i class="bi bi-instagram fs-4"></i></a>
                        <a href="#" class="social-icon-footer" style="background: black; color: white;"><i class="bi bi-tiktok fs-4"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-6">
                    <h5 class="fw-bold mb-4 text-white font-subheading">Liên kết</h5>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li><a href="index.php" class="text-decoration-none">Trang chủ</a></li>
                        <li><a href="menu.php" class="text-decoration-none">Thực đơn</a></li>
                        <li><a href="blog.php" class="text-decoration-none">Blog</a></li>
                        <li><a href="about.php" class="text-decoration-none">Về chúng tôi</a></li>
                        <li><a href="contact.php" class="text-decoration-none">Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-6">
                    <h5 class="fw-bold mb-4 text-white font-subheading">Hỗ trợ</h5>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li><a href="support.php?tab=guide" class="text-decoration-none">Hướng dẫn đặt hàng</a></li>
                        <li><a href="support.php?tab=privacy" class="text-decoration-none">Chính sách bảo mật</a></li>
                        <li><a href="support.php?tab=terms" class="text-decoration-none">Điều khoản sử dụng</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-4 text-white font-subheading">Liên hệ</h5>
                    <ul class="list-unstyled mb-0" style="opacity: 0.8;">
                        <li class="mb-3 d-flex gap-3"><i class="bi bi-geo-alt mt-1 text-warning"></i> TP. Cao Lãnh, Đồng Tháp</li>
                        <li class="mb-3 d-flex gap-3"><i class="bi bi-telephone mt-1 text-warning"></i> 1900 123 456</li>
                        <li class="mb-3 d-flex gap-3"><i class="bi bi-envelope mt-1 text-warning"></i> contact@tntcoffee.com</li>
                        <li class="mb-3 d-flex gap-3"><i class="bi bi-clock mt-1 text-warning"></i> 07:00 - 22:00 (Hàng ngày)</li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Footer -->
            <div class="border-top border-secondary pt-4 text-center small" style="opacity: 0.6;">
                &copy; <?php echo date('Y'); ?> TNT Coffee. All rights reserved.
            </div>
        </div>
    </footer>
    </div><!-- End .page-wrapper -->

    <!-- Product Detail Modal -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <!-- Left Side: Product Image -->
                        <div class="col-md-5">
                            <div class="h-100 bg-light">
                                <img id="modalProductImage" src="" class="img-fluid w-100 h-100" style="object-fit: cover; min-height: 450px;" alt="Product">
                            </div>
                        </div>
                        <!-- Right Side: Content -->
                        <div class="col-md-7 p-4 p-md-5 d-flex flex-column bg-white position-relative">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="z-index: 10;"></button>
                            
                            <div class="mb-4">
                                <span id="modalProductCategory" class="badge bg-primary-subtle text-primary mb-2 px-3 py-1 rounded-pill small fw-bold"></span>
                                <h3 id="modalProductName" class="fw-bold mb-2 text-dark" style="font-size: 2.25rem; letter-spacing: -0.5px;"></h3>
                                
                                <div class="h3 fw-bold text-primary mb-3" id="modalProductPrice" style="font-size: 1.75rem;"></div>
                                
                                <!-- Size Selection -->
                                <div id="sizeSelectionContainer" class="mb-4">
                                    <label class="form-label fw-bold mb-2">Chọn kích thước:</label>
                                    <div class="d-flex flex-wrap gap-2" id="sizeOptions">
                                        <!-- Will be populated by JS -->
                                    </div>
                                </div>

                                <div class="description-scroll pe-2" style="min-height: 60px; max-height: 150px; overflow-y: auto;">
                                    <p id="modalProductDesc" class="text-muted lh-base mb-0" style="font-size: 1.05rem;"></p>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-bold mb-1">Địa chỉ quán</h5>
                                <p class="text-muted mb-0">TP. Cao Lãnh, Đồng Tháp</p>
                            </div>
                            
                            <!-- Bottom Action Row -->
                            <div class="mt-auto pt-4 border-top">
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Quantity Selector -->
                                    <div class="input-group border rounded-pill bg-light" style="width: 130px; overflow: hidden; height: 48px;">
                                        <button class="btn btn-link text-dark text-decoration-none px-3" type="button" onclick="changeModalQty(-1)"><i class="bi bi-dash fs-5"></i></button>
                                        <input type="number" id="modalQuantity" class="form-control text-center border-0 bg-transparent fw-bold" value="1" min="1" readonly style="font-size: 1.1rem;">
                                        <button class="btn btn-link text-dark text-decoration-none px-3" type="button" onclick="changeModalQty(1)"><i class="bi bi-plus fs-5"></i></button>
                                    </div>
                                    <!-- Add to Cart Button (Brown Theme) -->
                                    <button id="modalAddToCartBtn" class="btn rounded-pill px-4 flex-grow-1 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 text-white h-100" style="background-color: #6F4E37; height: 48px; font-size: 1.05rem;">
                                        <i class="bi bi-bag-plus fs-5"></i>
                                        <span>Thêm vào giỏ</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
    const productModal = new bootstrap.Modal(document.getElementById('productDetailModal'));
    
    function showProductModal(id) {
        // Fetch product data
        fetch('<?= BASE_URL ?>/ajax/product_info.php?id=' + id)
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    const product = result.data;
                    document.getElementById('modalProductName').innerText = product.name;
                    document.getElementById('modalProductCategory').innerText = product.category_name;
                    document.getElementById('modalProductPrice').innerText = result.data.formatted_price;
                    document.getElementById('modalProductDesc').innerText = product.description || 'Hương vị tuyệt hảo từ TNT Coffee.';
                    document.getElementById('modalProductImage').src = product.image || 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=600&auto=format&fit=crop';
                    document.getElementById('modalQuantity').value = 1;

                    // Handle Sizes (Show S, M, L as requested)
                    const sizeContainer = document.getElementById('sizeOptions');
                    sizeContainer.innerHTML = '';
                    
                    const sizes = [
                        {key: 's', label: 'S', has: product.has_s, price: product.price_s},
                        {key: 'm', label: 'M', has: product.has_m, price: product.price_m},
                        {key: 'l', label: 'L', has: product.has_l, price: product.price_l}
                    ];

                    // Check if XL should also be shown (if it has data)
                    if (product.has_xl == 1) {
                        sizes.push({key: 'xl', label: 'XL', has: product.has_xl, price: product.price_xl});
                    }

                    let defaultSize = 'm';
                    let firstAvailable = null;

                    sizes.forEach(sz => {
                        if (sz.has == 1 && !firstAvailable) firstAvailable = sz.key;
                        
                        const div = document.createElement('div');
                        div.className = 'size-option';
                        const isChecked = (sz.key === 'm' && sz.has == 1) || (!product.has_m && sz.key === firstAvailable);
                        
                        div.innerHTML = `
                            <input type="radio" class="btn-check" name="product_size" id="size_${sz.key}" value="${sz.key.toUpperCase()}" ${isChecked ? 'checked' : ''}>
                            <label class="btn btn-outline-coffee rounded-pill px-4 py-2 fw-bold" for="size_${sz.key}" style="min-width: 60px;">${sz.label}</label>
                        `;
                        sizeContainer.appendChild(div);

                        // Update price when size changes
                        div.querySelector('input').addEventListener('change', function() {
                            if (this.checked) {
                                const price = (sz.has == 1 && sz.price > 0) ? sz.price : product.price;
                                document.getElementById('modalProductPrice').innerText = new Intl.NumberFormat('vi-VN').format(price) + 'đ';
                            }
                        });
                    });

                    // If NO sizes were found (e.g. all has_x are 0), show M as default
                    if (sizeContainer.innerHTML === '') {
                        const div = document.createElement('div');
                        div.className = 'size-option';
                        div.innerHTML = `
                            <input type="radio" class="btn-check" name="product_size" id="size_m" value="M" checked>
                            <label class="btn btn-outline-coffee rounded-pill px-4 py-2 fw-bold" for="size_m" style="min-width: 60px;">M</label>
                        `;
                        sizeContainer.appendChild(div);
                    }

                    // Final price sync for the initially checked size
                    const activeSizeInput = sizeContainer.querySelector('input:checked');
                    if (activeSizeInput) {
                        const activeKey = activeSizeInput.id.replace('size_', '');
                        const szData = sizes.find(s => s.key === activeKey);
                        const initialPrice = (szData && szData.has == 1 && szData.price > 0) ? szData.price : product.price;
                        document.getElementById('modalProductPrice').innerText = new Intl.NumberFormat('vi-VN').format(initialPrice) + 'đ';
                    }
                    
                    document.getElementById('modalAddToCartBtn').onclick = function() {
                        const selectedSizeInput = document.querySelector('input[name="product_size"]:checked');
                        const selectedSize = selectedSizeInput ? selectedSizeInput.value : 'M';
                        addToCart(product.id, document.getElementById('modalQuantity').value, selectedSize);
                        productModal.hide();
                    };
                    
                    productModal.show();
                } else {
                    alert('Không tìm thấy thông tin sản phẩm');
                }
            });
    }


    function changeModalQty(delta) {
        const input = document.getElementById('modalQuantity');
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        input.value = val;
    }

    // Reuse existing addToCart or define if not global
    if (typeof addToCart !== 'function') {
        window.addToCart = function(productId, quantity = 1, size = 'M') {
            fetch('<?= BASE_URL ?>/ajax/cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: parseInt(quantity),
                    size: size
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); 
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            });
        }
    }
    // Custom validation messages in Vietnamese
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.oninvalid = function(e) {
                e.target.setCustomValidity("");
                if (!e.target.validity.valid) {
                    if (e.target.type === 'email') {
                        e.target.setCustomValidity("Vui lòng nhập đúng định dạng email.");
                    } else {
                        e.target.setCustomValidity("Vui lòng điền vào trường này.");
                    }
                }
            };
            input.oninput = function(e) {
                e.target.setCustomValidity("");
            };
        });
    });
    </script>
</body>
</html>
