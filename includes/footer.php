    </div>
</div>
<script>
// Load scripts only if the page header did not already provide them.
// Re-loading Bootstrap from the CDN here double-registers its delegated
// handlers (e.g. dropdown toggling opens then instantly closes on one click).
(function() {
    function load(src) {
        document.write('<script src="' + src + '"><\/script>');
    }
    if (typeof window.jQuery === 'undefined') {
        load('https://code.jquery.com/jquery-3.7.0.min.js');
    }
    if (typeof window.bootstrap === 'undefined') {
        load('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js');
    }
    if (typeof window.jQuery !== 'undefined' && !window.jQuery.fn.dataTable) {
        load('https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js');
        load('https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js');
        load('https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js');
        load('https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js');
    }
})();
</script>
</body>
</html>
