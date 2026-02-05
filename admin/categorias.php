<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Lógica de guardar/editar/borrar
if (isset($_POST['guardar_categoria'])) {
    $nombre_nuevo = trim($conn->real_escape_string($_POST['nombre_cat']));
    $id_cat = isset($_POST['id_cat']) ? intval($_POST['id_cat']) : 0;

    if ($nombre_nuevo != "") {
        if ($id_cat == 0) { // Crear
            $check = $conn->query("SELECT id FROM categorias WHERE nombre = '$nombre_nuevo'");
            if ($check->num_rows == 0) {
                $conn->query("INSERT INTO categorias (nombre) VALUES ('$nombre_nuevo')");
                $_SESSION['flash_msg'] = "Categoría creada exitosamente.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "Esa categoría ya existe.";
                $_SESSION['flash_type'] = "warning";
            }
        } else { // Editar
            $conn->query("UPDATE categorias SET nombre = '$nombre_nuevo' WHERE id = $id_cat");
            $_SESSION['flash_msg'] = "Categoría actualizada.";
            $_SESSION['flash_type'] = "success";
        }
    }
    header("Location: categorias.php");
    exit();
}

if (isset($_GET['borrar_id'])) {
    $id_borrar = intval($_GET['borrar_id']);
    $check_docs = $conn->query("SELECT id FROM documentos WHERE categoria_id = $id_borrar AND activo = 1 LIMIT 1");

    if ($check_docs->num_rows > 0) {
        $_SESSION['flash_msg'] = "No se puede borrar la categoría porque tiene documentos activos asociados.";
        $_SESSION['flash_type'] = "danger";
    } else {
        $conn->query("DELETE FROM categorias WHERE id = $id_borrar");
        $_SESSION['flash_msg'] = "Categoría eliminada.";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: categorias.php");
    exit();
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">Gestión de Categorías</h1>

<div class="row">
    <!-- Formulario -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0" id="form-title">Nueva Categoría</h5>
            </div>
            <div class="card-body">
                <form action="categorias.php" method="POST">
                    <input type="hidden" name="id_cat" id="input_id" value="0">
                    <div class="mb-3">
                        <label for="input_nombre" class="form-label">Nombre</label>
                        <input type="text" name="nombre_cat" id="input_nombre" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="guardar_categoria" class="btn btn-primary" id="btn_guardar">Guardar</button>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-2 w-100 d-none" id="btn_cancelar" onclick="limpiarForm()">Cancelar Edición</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de categorías -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Categorías Existentes</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php
                    $res = $conn->query("SELECT c.id, c.nombre, COUNT(d.id) as total_docs 
                                         FROM categorias c
                                         LEFT JOIN documentos d ON c.id = d.categoria_id AND d.activo = 1
                                         GROUP BY c.id, c.nombre
                                         ORDER BY c.nombre ASC");
                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            $badge_class = $row['total_docs'] > 0 ? "bg-primary" : "bg-secondary";
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                                    <div>
                                        <span class='fw-bold'>" . htmlspecialchars($row['nombre']) . "</span>
                                        <span class='badge $badge_class rounded-pill ms-2'>{$row['total_docs']} docs</span>
                                    </div>
                                    <div>
                                        <button class='btn btn-sm btn-secondary me-1' onclick='editar(" . json_encode($row) . ")'><i class='fas fa-pencil-alt'></i></button>
                                        <a href='categorias.php?borrar_id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Estás seguro de borrar esta categoría?');\"><i class='fas fa-trash'></i></a>
                                    </div>
                                  </li>";
                        }
                    } else {
                        echo "<li class='list-group-item text-center text-muted'>No hay categorías registradas.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function editar(categoria) {
    document.getElementById('form-title').innerText = "Editando: " + categoria.nombre;
    document.getElementById('btn_guardar').innerText = "Actualizar";
    document.getElementById('input_id').value = categoria.id;
    document.getElementById('input_nombre').value = categoria.nombre;
    document.getElementById('btn_cancelar').classList.remove('d-none');
    window.scrollTo(0, 0);
}

function limpiarForm() {
    document.getElementById('form-title').innerText = "Nueva Categoría";
    document.getElementById('btn_guardar').innerText = "Guardar";
    document.getElementById('input_id').value = "0";
    document.getElementById('input_nombre').value = "";
    document.getElementById('btn_cancelar').classList.add('d-none');
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>