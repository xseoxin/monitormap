<?php
/**
 * Footer Template
 *
 * Common footer for all pages
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    &copy; <?= date('Y') ?> <?= e(Config::get('name', 'Google Maps Monitor', 'app')) ?>
                    <span class="version">v<?= e(APP_VERSION) ?></span>
                </div>
                <div class="footer-right">
                    <?php if (Auth::check()): ?>
                    <span class="footer-user">
                        Logged in as <strong><?= e(Auth::user()['username']) ?></strong>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Application JS -->
    <script src="<?= asset('assets/js/utils.js') ?>"></script>
    <script src="<?= asset('assets/js/api-client.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>

    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= asset($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Inline scripts -->
    <?php if (isset($inlineScript)): ?>
    <script>
        <?= $inlineScript ?>
    </script>
    <?php endif; ?>

    <script>
        // User dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userButton = document.querySelector('.user-button');
            const userMenu = document.querySelector('.user-menu');

            if (userButton && userMenu) {
                userButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                });

                document.addEventListener('click', function() {
                    userMenu.classList.remove('show');
                });
            }

            // Auto-hide flash messages after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
