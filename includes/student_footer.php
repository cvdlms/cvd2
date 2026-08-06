<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="//cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="//cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="//cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script>
(function () {
    var burger = document.getElementById('stdBurger');
    var backdrop = document.getElementById('stdBackdrop');
    function closeSb() {
        document.body.classList.remove('sb-open');
    }
    if (burger) {
        burger.addEventListener('click', function () {
            document.body.classList.toggle('sb-open');
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeSb);
    }
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeSb(); }
    });
})();
</script>
</main>
</body>
</html>
