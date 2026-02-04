    </div><!-- .page-container / .container -->
    <?php if (isLoggedIn() && (hasRole(ROLE_ADMIN) || hasRole(ROLE_REGISTRAR))): ?>
                </main><!-- .main-panel -->
            </div><!-- .admin-wrapper -->
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/public/js/main.js?v=<?php echo time(); ?>"></script>
    
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

