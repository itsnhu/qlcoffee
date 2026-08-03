    <?php if ($isLoggedIn): ?>
                <!-- Footer -->
                <footer class="footer-pharma border-top py-3 mt-auto bg-white">
                    <div class="container-fluid px-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <p class="mb-0 small text-muted">
                                <i class="bi bi-cup-hot-fill text-primary me-1"></i>
                                <strong>TNT Coffee</strong> &copy; <?php echo date('Y'); ?> - Hệ thống quản lý quán cà phê hiện đại
                            </p>
                            <p class="mb-0 text-muted small">
                                <i class="bi bi-code-slash me-1"></i> Phiên bản 1.0
                            </p>
                        </div>
                    </div>
                </footer>
                </div> <!-- end content-body -->
            </div> <!-- end main-content-wrapper -->
        </div> <!-- end dashboard-container -->
    <?php else: ?>
        <!-- Footer for public/auth pages -->
        <footer class="footer-pharma">
            <div class="container-fluid px-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <p class="mb-0">
                        <i class="bi bi-cup-hot-fill text-primary me-1"></i>
                        <strong>TNT Coffee</strong> &copy; <?php echo date('Y'); ?> - Hệ thống quản lý quán cà phê hiện đại
                    </p>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-code-slash me-1"></i> Phiên bản 1.0
                    </p>
                </div>
            </div>
        </footer>
            </div> <!-- end container -->
        </main>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });

            // Initialize DataTables with Vietnamese
            if ($.fn.DataTable) {
                $('.datatable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                    },
                    pageLength: 10,
                    responsive: true
                });
            }

            // Add animation to cards on load
            const cards = document.querySelectorAll('.stat-card, .card-pharma');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }

        // Format number
        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        }

        // Confirm delete
        function confirmDelete(message = 'Bạn có chắc chắn muốn xóa?') {
            return confirm(message);
        }

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })

            // Custom validation messages in Vietnamese
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
        })()
    </script>

    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html>
