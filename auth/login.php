<?php
session_start();
include '../config/db.php';

if (isset($_POST['ingresar'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, nombre_completo, password, rol FROM usuarios WHERE email = '$email' AND activo = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario_nombre'] = $row['nombre_completo'];
            $_SESSION['rol'] = $row['rol'];

            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION['flash_msg'] = "Contraseña incorrecta.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_msg'] = "Usuario no encontrado o inactivo.";
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Ayudas</title>
    <link href="https://bootswatch.com/5/minty/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card-login {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="card card-login shadow p-4">
        <h3 class="text-center mb-4">Iniciar Sesión</h3>
        <?php if (isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show"><?= $_SESSION['flash_msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="ingresar" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="mt-3 text-center">
            <span>Olvidaste tu Contraseña? </span>
            <a href="forgot_password.php" class="text-decoration-none">Recuperar Contraseña</a>
        </div>

        <div class="mt-3 text-center">
            <span>¿No tienes cuenta? </span>
            <a href="register.php" class="text-decoration-none">Regístrate aquí</a>
        </div>
    </div>
    <!-- Script necesario para que funcionen los alerts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>