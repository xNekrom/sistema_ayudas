    <!-- Fin del contenido principal -->
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
    // Inicializar DataTables en cualquier tabla con el id #dataTable
    $(document).ready(function() {
        if ($.fn.DataTable && $('#dataTable').length) { // Asegura que DataTables esté cargado y el elemento exista
            $('#dataTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/Spanish.json"
                }
            });
        }
    });

    // Pequeño script para marcar el link activo en el sidebar
    $(function(){
        var current = location.pathname;
        $('.sidebar .nav-link').each(function(){
            var $this = $(this);
            // if the current path is like this link, make it active
            if($this.attr('href').indexOf(current) !== -1){
                $this.addClass('active');
            }
        })
    })
</script>
</body>
</html>
