    </main>

    <!-- Footer -->
    <footer class="footer-store">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-brand">
                        <i class="bi bi-capsule-pill"></i>
                        PharmaManager
                    </div>
                    <p class="text-gray-400 mb-3">Nhà thuốc trực tuyến uy tín, cung cấp đa dạng các loại thuốc, vitamin và thực phẩm chức năng chính hãng với giá tốt nhất.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-gray-400 fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-gray-400 fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-gray-400 fs-5"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="text-gray-400 fs-5"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-6 col-md-2 mb-4">
                    <h6>Về chúng tôi</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Giới thiệu</a></li>
                        <li class="mb-2"><a href="#">Hệ thống cửa hàng</a></li>
                        <li class="mb-2"><a href="#">Tin tức sức khỏe</a></li>
                        <li class="mb-2"><a href="#">Tuyển dụng</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 mb-4">
                    <h6>Hỗ trợ khách hàng</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Hướng dẫn mua hàng</a></li>
                        <li class="mb-2"><a href="#">Chính sách đổi trả</a></li>
                        <li class="mb-2"><a href="#">Chính sách vận chuyển</a></li>
                        <li class="mb-2"><a href="#">Chính sách bảo mật</a></li>
                        <li class="mb-2"><a href="#">Điều khoản sử dụng</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6>Liên hệ</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="bi bi-geo-alt-fill me-2 text-primary-400"></i>
                            <span>123 Đường ABC, Quận 1, TP. Hồ Chí Minh</span>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone-fill me-2 text-primary-400"></i>
                            <a href="tel:19001234">1900 1234</a>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope-fill me-2 text-primary-400"></i>
                            <a href="mailto:support@pharmamanager.vn">support@pharmamanager.vn</a>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-clock-fill me-2 text-primary-400"></i>
                            <span>8:00 - 22:00 (T2-CN)</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Payment & Certification -->
            <div class="row py-3 border-top border-secondary">
                <div class="col-md-6 mb-3 mb-md-0">
                    <small class="text-gray-500">Phương thức thanh toán:</small>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge bg-light text-dark"><i class="bi bi-cash me-1"></i>COD</span>
                        <span class="badge bg-light text-dark"><i class="bi bi-bank me-1"></i>Chuyển khoản</span>
                        <span class="badge bg-light text-dark"><i class="bi bi-credit-card me-1"></i>Visa/Master</span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-gray-500">Chứng nhận:</small>
                    <div class="d-flex gap-2 mt-2 justify-content-md-end">
                        <span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i>Bộ Y tế</span>
                        <span class="badge bg-info"><i class="bi bi-shield-check me-1"></i>An toàn</span>
                    </div>
                </div>
            </div>

            <div class="footer-bottom text-center">
                <small class="text-gray-500">&copy; <?= date('Y') ?> PharmaManager. Bảo lưu mọi quyền. Giấy phép kinh doanh thuốc số: 123456/ĐĐKKD</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add to cart function
        function addToCart(medicineId, quantity = 1) {
            fetch('<?= BASE_URL ?>/user/ajax/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    medicine_id: medicineId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart badge
                    const badge = document.querySelector('.cart-badge');
                    if (badge) {
                        badge.textContent = data.cartCount;
                        badge.style.display = 'inline';
                    } else {
                        location.reload();
                    }
                    showToast('Đã thêm vào giỏ hàng!', 'success');
                } else {
                    if (data.requireLogin) {
                        if (confirm('Vui lòng đăng nhập để thêm vào giỏ hàng. Đăng nhập ngay?')) {
                            window.location.href = '<?= BASE_URL ?>/login.php?type=customer';
                        }
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra', 'danger');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'danger');
            });
        }

        // Toast notification function
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';

            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi ${iconClass} me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Quantity controls
        function updateQuantity(input, change) {
            const currentVal = parseInt(input.value) || 1;
            const newVal = Math.max(1, currentVal + change);
            input.value = newVal;

            // Trigger change event if needed
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    </script>
</body>
</html>
