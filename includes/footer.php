    </main>

    <!-- Footer -->
    <footer class="site-footer bg-dark text-white mt-5">
        <div class="container">
            <!-- Main Footer Content -->
            <div class="row py-5">
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="DUET Library" width="50" height="50" class="me-3">
                            <div>
                                <h4 class="mb-0 fw-bold">
                                    <span class="text-primary">DUET</span>
                                    <span class="text-white">Library</span>
                                </h4>
                                <small class="text-muted">Digital Learning Platform</small>
                            </div>
                        </div>
                        <p class="text-light opacity-75 mb-4">
                            Empowering DUET students with comprehensive access to academic resources,
                            textbooks, and research materials. Your gateway to knowledge and educational excellence.
                        </p>
                        <!-- Social Links -->
                        <div class="social-links d-flex gap-3">
                            <a href="#" class="social-link" aria-label="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Twitter">
                                <i class="bi bi-twitter"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <i class="bi bi-linkedin"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Email">
                                <i class="bi bi-envelope"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 col-6 mb-4">
                    <h5 class="fw-semibold mb-3 text-primary">Quick Links</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/index.php" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/categories.php" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Categories
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/upload.php" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Upload Book
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/request.php" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Request Book
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-lg-2 col-md-6 col-6 mb-4">
                    <h5 class="fw-semibold mb-3 text-primary">Support</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Help Center
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>FAQ
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Contact Us
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-light text-decoration-none opacity-75">
                                <i class="bi bi-chevron-right me-1"></i>Privacy Policy
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-semibold mb-3 text-primary">Contact Information</h5>
                    <div class="contact-info">
                        <div class="contact-item d-flex mb-3">
                            <div class="contact-icon me-3">
                                <i class="bi bi-geo-alt-fill text-primary fs-5"></i>
                            </div>
                            <div class="contact-text">
                                <p class="text-light opacity-75 mb-0 small">
                                    Dhaka University of Engineering & Technology<br>
                                    Gazipur-1707, Bangladesh
                                </p>
                            </div>
                        </div>
                        <div class="contact-item d-flex mb-3">
                            <div class="contact-icon me-3">
                                <i class="bi bi-envelope-fill text-primary fs-5"></i>
                            </div>
                            <div class="contact-text">
                                <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="text-light text-decoration-none opacity-75 small">
                                    <?php echo ADMIN_EMAIL; ?>
                                </a>
                            </div>
                        </div>
                        <div class="contact-item d-flex mb-3">
                            <div class="contact-icon me-3">
                                <i class="bi bi-telephone-fill text-primary fs-5"></i>
                            </div>
                            <div class="contact-text">
                                <a href="tel:+8801234567890" class="text-light text-decoration-none opacity-75 small">
                                    +880 123 456 7890
                                </a>
                            </div>
                        </div>
                        <div class="contact-item d-flex">
                            <div class="contact-icon me-3">
                                <i class="bi bi-clock-fill text-primary fs-5"></i>
                            </div>
                            <div class="contact-text">
                                <p class="text-light opacity-75 mb-0 small">
                                    24/7 Online Access
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom border-top border-secondary pt-4 pb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="text-light opacity-75 mb-0 small">
                            © <?php echo date('Y'); ?> DUET PDF Library. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="text-light opacity-75 mb-0 small">
                            Made with <i class="bi bi-heart-fill text-danger"></i> for DUET Students
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary back-to-top position-fixed bottom-0 end-0 m-4 rounded-circle shadow-lg d-none"
        style="width: 50px; height: 50px; z-index: 1000;" aria-label="Back to top">
        <i class="bi bi-arrow-up fs-5"></i>
    </button>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/mobile-enhancements.js"></script>

    </body>

    </html>