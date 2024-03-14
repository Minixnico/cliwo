<?php
session_start();

// Redirigir si el usuario no está logueado o si no es el administrador (usuario_id != 1)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
} elseif ($_SESSION['usuario_id'] != 1) {
    $_SESSION['error'] = "No tienes permiso para acceder";
    header("Location: paginaTrabajador.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLIWO</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>
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
                header("Location: login.php");
                exit;
            }

            // Aquí agregas la lógica para contar las solicitudes pendientes
            $conexion = new mysqli("localhost", "root", "", "cliwodb"); // Ajusta estos valores a tu configuración
            if ($conexion->connect_error) {
                die("Error de conexión: " . $conexion->connect_error);
            }

            $consultaPendientes = "SELECT COUNT(*) as totalPendientes FROM ausencias WHERE estado = 'Pendiente'";
            $resultadoPendientes = $conexion->query($consultaPendientes);
            $fila = $resultadoPendientes->fetch_assoc();
            $pendientes = $fila['totalPendientes'];
            ?>
        </div>

        <div class="menu">
            <a href="fichaje.php"> <img src="img/fichaje.png" alt="fichar" class="landing">Fichar</a>
            <a href="trabajadores.php"><img src="img/trabajadores.png" alt="trabajadores" class="landing"> Mis
                trabajadores</a>
            <a href="horarios.php"> <img src="img/horario.png" alt="horarios" class="landing">Horarios</a>
            <a href="gestionarAusencias.php"><img src="img/vacaciones.png" alt="Gestionar ausencias"
                    class="landing">Vacaciones y Ausencias
                <?php if ($pendientes > 0) {
                    echo " <span class='notificacion'>$pendientes</span>";
                } ?>
            </a>
            <a href="logica/cerrarSesion.php"><img src="img/cerrarSesion.png" alt="Cerrar sesión" class="landing">Cerrar
                sesión</a>
        </div>
    </div>

    <footer>
        <div class="legal-info">
            <p>© 2024 Cliwo. Click and Work. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>

</html>