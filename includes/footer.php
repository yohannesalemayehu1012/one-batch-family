<footer class="footer-modern mt-5 text-white">
    <div class="container py-5">
        <div class="row g-4 align-items-start">
            <div class="col-lg-4">
                <div class="footer-brand-card p-4 rounded-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="<?php echo BASE_URL; ?>assets/images/logo/logo.svg" alt="Dae Batch Family logo" class="brand-icon">
                        <div>
                            <div class="fw-bold fs-5"><?php echo htmlspecialchars(APP_NAME); ?></div>
                            <small class="text-white-50">One Family. One Heart. One Faith.</small>
                        </div>
                    </div>
                    <p class="text-white-50 mb-0">A warm, spiritual, and connected family community built for joy, prayer, and shared memories.</p>
                </div>
            </div>
            <div class="col-lg-2 col-sm-6">
                <div class="footer-link-card p-4 rounded-4 h-100">
                    <h6 class="fw-semibold mb-3">Quick Links</h6>
                    <ul class="list-unstyled mb-0 footer-links">
                        <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/about.php">About</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/members.php">Members</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/events.php">Events</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="footer-link-card p-4 rounded-4 h-100">
                    <h6 class="fw-semibold mb-3">Resources</h6>
                    <ul class="list-unstyled mb-0 footer-links">
                        <li><a href="<?php echo BASE_URL; ?>pages/prayer.php">Prayer</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/memories.php">Memories</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/gallery.php">Gallery</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="footer-link-card p-4 rounded-4 h-100">
                    <h6 class="fw-semibold mb-3">Follow Us</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="#" class="btn btn-sm footer-social rounded-pill"><i class="fab fa-facebook-f"></i> Facebook</a>
                        <a href="#" class="btn btn-sm footer-social rounded-pill"><i class="fab fa-telegram-plane"></i> Telegram</a>
                        <a href="#" class="btn btn-sm footer-social rounded-pill"><i class="fab fa-instagram"></i> Instagram</a>
                        <a href="#" class="btn btn-sm footer-social rounded-pill"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom mt-4 pt-4 border-top border-white border-opacity-10 d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <p class="mb-0 text-white-50">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(APP_NAME); ?></p>
            <p class="mb-0 text-white-50">One Family. One Heart. One Faith.</p>
        </div>
    </div>
</footer>
</body>

</html>