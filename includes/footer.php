<script>
// Automatické zavření alertů po 3 sekundách
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            } else {
                alert.classList.remove('show');
                setTimeout(function() {
                    alert.remove();
                }, 150);
            }
        }, 2000);
    });
});
</script>

</main> <!-- .container -->
<footer class="text-center text-muted bg-light py-3 mt-auto border-top">
    <small>&copy; <?= date('Y') ?> Online odkladiště souborů, velmi jednoduché</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>