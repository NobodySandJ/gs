    </div><!-- .main-content -->
    
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>
    <?php if (isset($include_dashboard_js) && $include_dashboard_js): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?= ASSETS_URL ?>/js/dashboard.js"></script>
    <?php endif; ?>
</body>
</html>
