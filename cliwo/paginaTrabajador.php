<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$usuario_id = $_SESSION['usuario_id'];

// Verifica si existen solicitudes aceptadas o rechazadas pendientes por notificar al trabajador
$consulta = $conexion->prepare("SELECT COUNT(*) AS pendientes FROM ausencias WHERE usuario_id = ? AND notificado = 0 AND estado IN ('Aceptada', 'Rechazada')");
$consulta->bind_param("i", $usuario_id);
$consulta->execute();
$resultado = $consulta->get_result();
$fila = $resultado->fetch_assoc();

$tieneNotificaciones = $fila['pendientes'] > 0;


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLIWO</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="mensaje-error">
            <?= $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="logo">
            <img src="img/logo.png" alt="Logo de CLIWO">
        </div>

        <div class="contenido">
            <?php
            if (isset($_SESSION['nombre_usuario'])) {
                $usuario = $_SESSION['nombre_usuario'];
                echo "<h1>BIENVENIDO a CLIWO, $usuario</h1>";
            } else {
                exit;
            }
            ?>
        </div>

        <div class="menu">
            <a href="fichaje.php"><img src="img/fichaje.png" alt="fichar" class="landing">Fichar</a>
            <a href="horariotrabajador.php"><img src="img/horario.png" alt="Mi horario" class="landing">Mi horario</a>
            <a href="ausencias.php">
                <img src="img/Vacaciones.png" alt="Gestionar vacaciones" class="landing">Ausencia y Vacaciones
                <?php if ($tieneNotificaciones): ?>
                    <!-- Icono de notificación -->
                    <span class="notificacion"></span>
                <?php endif; ?>
            </a>
            <a href="cambiarContrasena.php"><img src="img/cambiarContrasena.png" alt="Cambiar contraseña"
                    class="landing">Cambiar Contraseña</a>
            <a href="logica/cerrarSesion.php"><img src="img/cerrarSesion.png" alt="Cerrar sesión" class="landing">Cerrar
                sesión</a>
        </div>

    </div>

    <footer>
        <div class="legal-info">
            <p>© 2024 Cliwo.Click and Work. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>

</html>

<?php $conexion->close(); ?>