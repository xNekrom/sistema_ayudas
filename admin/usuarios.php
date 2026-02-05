<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// SEGURIDAD: Verificar que sea Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

// LÓGICA DE ACTUALIZAR ROL
if (isset($_POST['actualizar_rol'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $nuevo_rol = $_POST['nuevo_rol'];
    
    // Evitar que el admin se quite permisos a sí mismo por error
    if ($id_usuario == $_SESSION['usuario_id'] && $nuevo_rol != 'Admin') {
        $_SESSION['flash_msg'] = "⚠️ No puedes quitarte el rol de Admin a ti mismo.";
        $_SESSION['flash_type'] = "warning";
    } else {
        $sql = "UPDATE usuarios SET rol = '$nuevo_rol' WHERE id = $id_usuario";
        if ($conn->query($sql)) {
            $_SESSION['flash_msg'] = "✅ Rol actualizado correctamente.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_msg'] = "❌ Error: " . $conn->error;
            $_SESSION['flash_type'] = "danger";
        }
    }
    header("Location: usuarios.php");
    exit();
}

// LÓGICA DE BORRAR/DESACTIVAR USUARIO
if (isset($_GET['borrar_id'])) {
    $id_borrar = intval($_GET['borrar_id']);
    
    if ($id_borrar == $_SESSION['usuario_id']) {
        $_SESSION['flash_msg'] = "❌ No puedes eliminar tu propia cuenta mientras la usas.";
        $_SESSION['flash_type'] = "danger";
    } else {
        $conn->query("UPDATE usuarios SET activo = 0 WHERE id = $id_borrar");
        $_SESSION['flash_msg'] = "🗑️ Usuario desactivado.";
        $_SESSION['flash_type'] = "info";
    }
    header("Location: usuarios.php");
    exit();
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">Administración de Usuarios</h1>

<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Lista de Usuarios</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="dataTable" class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Rol Actual</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Traemos todos los usuarios (activos e inactivos)
                    $sql = "SELECT * FROM usuarios ORDER BY activo DESC, id ASC";
                    $result = $conn->query($sql);

                    while ($row = $result->fetch_assoc()) {
                        $bg_estado = $row['activo'] ? 'bg-success' : 'bg-secondary';
                        $txt_estado = $row['activo'] ? 'Activo' : 'Inactivo';
                        
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge <?php echo $bg_estado; ?>"><?php echo $txt_estado; ?></span></td>
                            
                            <td>
                                <form method="POST" action="usuarios.php" class="d-flex align-items-center">
                                    <input type="hidden" name="id_usuario" value="<?php echo $row['id']; ?>">
                                    <select name="nuevo_rol" class="form-select form-select-sm me-2">
                                        <option value="Operador" <?php echo ($row['rol']=='Operador'?'selected':''); ?>>Operador</option>
                                        <option value="Calidad" <?php echo ($row['rol']=='Calidad'?'selected':''); ?>>Calidad</option>
                                        <option value="Tecnico" <?php echo ($row['rol']=='Tecnico'?'selected':''); ?>>Técnico</option>
                                        <option value="Admin" <?php echo ($row['rol']=='Admin'?'selected':''); ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="actualizar_rol" class="btn btn-sm btn-primary" title="Guardar Rol"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                            <td>
                                <?php if($row['activo']): ?>
                                    <a href="?borrar_id=<?php echo $row['id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Seguro que deseas desactivar a este usuario?');" title="Desactivar Usuario">
                                       <i class="fas fa-user-slash"></i> Desactivar
                                    </a>
                                <?php else: ?>
                                     <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>