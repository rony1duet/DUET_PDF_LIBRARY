    </main> <!-- Footer -->
    <footer class="site-footer bg-dark text-white mt-auto">
        <div class="container">
            <div class="row align-items-center py-2">
                <!-- Brand Section -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="DUET Library" width="28" height="28" class="me-2">
                        <div class="footer-brand-text">
                            <span class="fw-semibold">
                                <span class="text-primary">DUET</span>
                                <span class="text-white">Library</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Links & Social Section -->
                <div class="col-md-6">
                    <div class="d-flex justify-content-center justify-content-md-end align-items-center">
                        <div class="footer-links d-flex align-items-center">
                            <a href="mailto:<?php echo defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@duetlibrary.com'; ?>" class="footer-link me-3" title="Email us">
                                <i class="bi bi-envelope me-1"></i>
                                <span class="d-none d-sm-inline">Contact</span>
                            </a>
                            <a href="tel:+8801571208220" class="footer-link me-3" title="Call us">
                                <i class="bi bi-telephone me-1"></i>
                                <span class="d-none d-sm-inline">Call</span>
                            </a>
                            <a href="#" class="footer-link me-3" title="Follow us on Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" class="footer-link" title="Visit our website">
                                <i class="bi bi-globe"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/mobile-enhancements.js"></script>

    </body>

    </html>