<?php
session_start();
include '../config/db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token inválido.");
}

// Validar token y expiración
$stmt = $conn->prepare("
    SELECT id 
    FROM usuarios 
    WHERE reset_token = ? 
    AND token_expira > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("El enlace es inválido o ya expiró.");
}

$user = $result->fetch_assoc();

// Procesar cambio de contraseña
if (isset($_POST['cambiar'])) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET password = ?, reset_token = NULL, token_expira = NULL 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hash, $user['id']);
        $stmt->execute();

        $_SESSION['flash_msg'] = "Contraseña actualizada correctamente.";
        $_SESSION['flash_type'] = "success";
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link href="https://bootswatch.com/5/minty/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

<div class="card shadow p-4" style="max-width:400px;width:100%;">
    <h4 class="text-center mb-3">Nueva Contraseña</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Nueva Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Confirmar Contraseña</label>
            <input type="password" name="confirm" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100" name="cambiar">
            Cambiar Contraseña
        </button>
    </form>
</div>

</body>
</html>
