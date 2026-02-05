<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si no hay sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Conexión a la BD (asumiendo que los archivos que incluyan este header estarán en el raíz o en subcarpetas de primer nivel)
// Se usa un path relativo robusto
require_once __DIR__ . '/../config/db.php';

$rol = $_SESSION['rol'];
$nombre_usuario = $_SESSION['usuario_nombre'];

// Lógica para el badge de reportes pendientes
$pendientes = 0;
if (in_array($rol, ['Admin', 'Calidad'])) {
    $res_count = $conn->query("SELECT count(*) as total FROM reportes_errores WHERE estado = 'Pendiente'");
    if ($res_count) {
        $pendientes = $res_count->fetch_assoc()['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ayudas Visuales</title>
    <!-- Estilos -->
    <link href="https://bootswatch.com/5/minty/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/sistema_ayudas/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-tools"></i> Admin Panel</h3>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link active" href="/sistema_ayudas/index.php">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>

        <?php if ($rol == 'Admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/admin/usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/admin/clientes.php"><i class="fas fa-building"></i> Clientes</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/admin/categorias.php"><i class="fas fa-tags"></i> Categorías</a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/admin/dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard</a>
            </li>
        <?php endif; ?>

        <?php if (in_array($rol, ['Admin', 'Calidad'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/quality/gestion_reportes.php">
                    <i class="fas fa-exclamation-triangle"></i> Reportes
                    <?= ($pendientes > 0 ? "<span class='badge bg-danger ms-2'>$pendientes</span>" : "") ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/quality/ver_inspecciones.php"><i class="fas fa-check-square"></i> Inspecciones</a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/quality/auditoria.php"><i class="fas fa-book"></i> Auditar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/quality/historial.php"><i class="fas fa-history"></i> Historial</a>
            </li>
        <?php endif; ?>

        <?php if ($rol == 'Admin'): ?>
             <li class="nav-item">
                <a class="nav-link" href="/sistema_ayudas/admin/eliminados.php"><i class="fas fa-trash"></i> Papelera</a>
            </li>
        <?php endif; ?>

        <li class="nav-item mt-auto">
            <a class="nav-link" href="/sistema_ayudas/auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </li>
    </ul>
</div>

<div id="content">
    <div class="user-info">
        <span>Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> (<?php echo htmlspecialchars($rol); ?>)</span>
    </div>

    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show">
            <?= $_SESSION['flash_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
    
    <!-- El contenido principal de cada página irá aquí -->
