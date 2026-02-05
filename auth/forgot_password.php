<?php
session_start();
include '../config/db.php'; // Se mantiene la ruta de conexión

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Carga PHPMailer desde tu vendor

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/**
 * Función para enviar el correo electrónico mediante SMTP
 */
function enviarCorreo($destinatario, $enlace)
{
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor (Ajusta con tus datos SMTP cuando los tengas)
        $mail->isSMTP();
        $mail->Host     = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port     = $_ENV['SMTP_PORT'];

        // Destinatarios
        $mail->setFrom($_ENV['SMTP_USER'], 'Sistema de Ayudas');
        $mail->addAddress($destinatario);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Recuperar Contraseña - Sistema de Ayudas';
        $mail->Body    = "
            <h3>Solicitud de restablecimiento de contraseña</h3>
            <p>Has solicitado restablecer tu contraseña en el Sistema de Ayudas.</p>
            <p>Haz clic en el siguiente enlace para continuar (expira en 1 hora):</p>
            <a href='$enlace' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Restablecer Contraseña</a>
            <br><br>
            <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>";

        return $mail->send();
    } catch (Exception $e) {
        echo "<pre>";
        echo "Mailer Error: " . $mail->ErrorInfo;
        echo "</pre>";
        exit;
    }
}

if (isset($_POST['recuperar'])) {
    $email = $conn->real_escape_string($_POST['email']);

    // Validamos que el usuario exista y esté activo en la tabla usuarios
    $sql = "SELECT id FROM usuarios WHERE email = '$email' AND activo = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32)); // Token de seguridad único
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Guardamos el token y su expiración en la base de datos
        $updateSql = "UPDATE usuarios SET reset_token = '$token', token_expira = '$expira' WHERE id = " . $user['id'];
        $conn->query($updateSql);

        // Generamos el enlace (asegúrate de que la ruta coincida con tu estructura)
        $enlace = "http://localhost/sistema_ayudas/auth/reset_password.php?token=$token";

        if (enviarCorreo($email, $enlace)) {
            $_SESSION['flash_msg'] = "Se ha enviado un enlace a tu correo electrónico.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_msg'] = "Error al enviar el correo. Inténtalo más tarde.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_msg'] = "El correo no está registrado o el usuario está inactivo.";
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema Ayudas</title>
    <link href="https://bootswatch.com/5/minty/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card-forgot {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="card card-forgot shadow p-4">
        <h3 class="text-center mb-4">Recuperar Acceso</h3>

        <?php if (isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show">
                <?= $_SESSION['flash_msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="tu@correo.com" required autofocus>
            </div>
            <button type="submit" name="recuperar" class="btn btn-primary w-100">Enviar Enlace de Recuperación</button>
        </form>

        <div class="mt-3 text-center">
            <a href="login.php" class="text-decoration-none">Volver al inicio de sesión</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>