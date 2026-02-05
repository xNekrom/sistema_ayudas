<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php"); exit();
}

// 1. LÓGICA PARA AGREGAR / EDITAR
if (isset($_POST['nombre'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $sql = "UPDATE clientes SET nombre = '$nombre' WHERE id = $id";
    } else {
        $sql = "INSERT INTO clientes (nombre, estatus) VALUES ('$nombre', 1)";
    }
    $conn->query($sql);
    header("Location: clientes.php"); // Redirigir para evitar reenvío de formulario
    exit();
}

// 2. LÓGICA PARA CAMBIAR ESTATUS (Activar/Desactivar)
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $conn->query("UPDATE clientes SET estatus = 1 - estatus WHERE id = $id");
    header("Location: clientes.php"); exit();
}

// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">Gestión de Clientes</h1>

<div class="row">
    <!-- Formulario para agregar/editar -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 id="form-title" class="mb-0">Agregar Nuevo Cliente</h5>
            </div>
            <div class="card-body">
                <form id="formCliente" action="clientes.php" method="POST">
                    <input type="hidden" name="id" id="id_cliente">
                    <div class="mb-3">
                        <label for="nombre_cliente" class="form-label">Nombre del Cliente</label>
                        <input type="text" name="nombre" id="nombre_cliente" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" onclick="limpiarForm()" class="btn btn-secondary btn-sm">Limpiar / Cancelar Edición</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de clientes -->
    <div class="col-md-8">
        <div class="card shadow-sm">
             <div class="card-header">
                <h5 class="mb-0">Clientes Existentes</h5>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-center">Estatus</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM clientes ORDER BY nombre ASC");
                        while($c = $res->fetch_assoc()): 
                            $estatus_clase = $c['estatus'] ? 'success' : 'danger';
                            $estatus_texto = $c['estatus'] ? 'Activo' : 'Inactivo';
                            $toggle_clase = $c['estatus'] ? 'btn-outline-danger' : 'btn-outline-success';
                            $toggle_texto = $c['estatus'] ? 'Desactivar' : 'Activar';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                            <td class="text-center"><span class="badge bg-<?php echo $estatus_clase; ?>"><?php echo $estatus_texto; ?></span></td>
                            <td class="text-center">
                                <button onclick='editarCliente(<?php echo json_encode($c); ?>)' class="btn btn-sm btn-secondary">Editar</button>
                                <a href="?toggle_id=<?php echo $c['id']; ?>" class="btn btn-sm <?php echo $toggle_clase; ?>"><?php echo $toggle_texto; ?></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function editarCliente(cliente) {
    document.getElementById('id_cliente').value = cliente.id;
    document.getElementById('nombre_cliente').value = cliente.nombre;
    document.getElementById('form-title').innerText = 'Editando Cliente';
    window.scrollTo(0, 0); // Subir al inicio de la página para ver el form
}

function limpiarForm() {
    document.getElementById('formCliente').reset();
    document.getElementById('id_cliente').value = '';
    document.getElementById('form-title').innerText = 'Agregar Nuevo Cliente';
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>