<?php
session_start(); // Iniciar sesión al principio del script
include '../config/db.php'; 

if (isset($_POST['registrar'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password_plano = $_POST['password'];
    $rol = $_POST['rol'];

    $continuar = true;

    if (!preg_match("/^[a-zA-ZñÑáéíóúÁÉÍÓÚüÜ\s]+$/", $nombre)) {
        $_SESSION['flash_msg'] = "❌ El nombre contiene caracteres no válidos (emojis o símbolos).";
        $_SESSION['flash_type'] = "danger";
        $continuar = false;
    }

    if ($continuar && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_msg'] = "❌ El correo electrónico no es válido.";
        $_SESSION['flash_type'] = "danger";
        $continuar = false;
    }

    if ($continuar) {
        if (strlen($password_plano) <= 5) {
            $_SESSION['flash_msg'] = "❌ La contraseña debe tener más de 5 caracteres.";
            $_SESSION['flash_type'] = "danger";
            $continuar = false;
        } 
        elseif (!preg_match('/^[\x20-\x7E]+$/', $password_plano)) {
            $_SESSION['flash_msg'] = "❌ La contraseña contiene caracteres no permitidos (emojis).";
            $_SESSION['flash_type'] = "danger";
            $continuar = false;
        }
    }

    if ($continuar) {
        $email_safe = $conn->real_escape_string($email);
        $check_email = $conn->query("SELECT id FROM usuarios WHERE email = '$email_safe'");
        
        if ($check_email->num_rows > 0) {
            $_SESSION['flash_msg'] = "❌ Este correo ya está registrado.";
            $_SESSION['flash_type'] = "danger";
        } else {
            $pass_encriptada = password_hash($password_plano, PASSWORD_DEFAULT);
            $nombre_safe = $conn->real_escape_string($nombre);

            $sql = "INSERT INTO usuarios (nombre_completo, email, password, rol, activo) 
                    VALUES ('$nombre_safe', '$email_safe', '$pass_encriptada', '$rol', 1)";

            if ($conn->query($sql) === TRUE) {
                $_SESSION['flash_msg'] = "✅ Usuario registrado con éxito. <a href='login.php' class='alert-link'>Inicia sesión aquí</a>.";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "❌ Error en BD: " . $conn->error;
                $_SESSION['flash_type'] = "danger";
            }
        }
    }
    header("Location: register.php"); // Redirigir para mostrar el mensaje flash
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema Ayudas</title>
    <link href="https://bootswatch.com/5/minty/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card-reg { width: 100%; max-width: 500px; }
    </style>
</head>
<body>

    <div class="card card-reg shadow p-4">
        <h3 class="text-center mb-4">Crear Cuenta</h3>
            
        <?php if (isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show"><?= $_SESSION['flash_msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="nombre" class="form-control" 
                       placeholder="Ej: Juan Pérez" required
                       pattern="[a-zA-ZñÑáéíóúÁÉÍÓÚüÜ\s]+"
                       title="Solo letras y espacios, sin emojis."
                       oninput="this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚüÜ\s]/g, '')">
            </div>

            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="nombre@empresa.com" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required
                       minlength="6"
                       placeholder="Mínimo 6 caracteres"
                       oninput="this.value = this.value.replace(/[^\x20-\x7E]/g, '')">
                <div class="form-text">Mínimo 6 caracteres. Sin emojis.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Rol / Puesto</label>
                <select name="rol" class="form-select">
                    <option value="Operador">Operador</option>
                    <option value="Calidad">Calidad</option>
                    <option value='Tecnico'>Técnico</option>
                </select>
            </div>

            <button type="submit" name="registrar" class="btn btn-primary w-100 mb-3">Registrarse</button>
        </form>

        <div class="text-center">
            <a href="login.php" class="text-decoration-none">¿Ya tienes cuenta? Inicia Sesión</a>
        </div>
    </div>
    <!-- Script necesario para que funcionen los alerts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>