    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> RBM Schedule Management System. All rights reserved.</p>
    </footer>
    <script src="<?php echo JS_URL; ?>/script.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../assets/js/script.js')); ?>"></script>
</body>
</html>