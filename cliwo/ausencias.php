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

// Consulta para obtener las solicitudes de ausencia del usuario que han sido aceptadas o rechazadas y no notificadas
$consultaNotificaciones = $conexion->prepare("SELECT id FROM ausencias WHERE usuario_id = ? AND estado IN ('Aceptada', 'Rechazada') AND notificado = 0");
$consultaNotificaciones->bind_param("i", $usuario_id);
$consultaNotificaciones->execute();
$resultadoNotificaciones = $consultaNotificaciones->get_result();
$tieneNotificaciones = $resultadoNotificaciones->num_rows > 0;

// Marcar las solicitudes como notificadas una vez que el usuario visita esta página
if ($tieneNotificaciones) {
    $actualizarNotificaciones = $conexion->prepare("UPDATE ausencias SET notificado = 1 WHERE usuario_id = ? AND estado IN ('Aceptada', 'Rechazada') AND notificado = 0");
    $actualizarNotificaciones->bind_param("i", $usuario_id);
    $actualizarNotificaciones->execute();
}

// Procesa el formulario de solicitud de ausencia si se enviaron los datos por POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tipo_ausencia'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['motivo'])) {
    // Asigna los datos enviados a variables.
    $tipo_ausencia = $_POST['tipo_ausencia'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $motivo = $_POST['motivo'];

    // Verifica la validez de las fechas.
    if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        // Si la fecha de inicio es posterior a la fecha de fin, guarda un mensaje de error en la sesión y redirige.
        $_SESSION['error'] = "La fecha de inicio debe ser anterior a la fecha de fin.";
        header("Location: ausencias.php");
        exit;
    }

    // Inserta la solicitud en la base de datos.
    $consulta = $conexion->prepare("INSERT INTO ausencias (usuario_id, tipo_ausencia, fecha_inicio, fecha_fin, motivo, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')");
    $consulta->bind_param("issss", $usuario_id, $tipo_ausencia, $fecha_inicio, $fecha_fin, $motivo);
    if ($consulta->execute()) {
        // Si la inserción es exitosa, guarda un mensaje de éxito en la sesión.
        $_SESSION['mensaje'] = "Solicitud de ausencia enviada correctamente. Esperando aprobación.";
    } else {
        // Si hay un error, guarda el mensaje de error en la sesión.
        $_SESSION['error'] = "Error al enviar la solicitud de ausencia: " . $consulta->error;
    }

    // Redirige a la misma página para mostrar mensajes o continuar el proceso.
    header("Location: ausencias.php");
    exit;
}

// Obtiene el nombre del usuario de la sesión, con un valor predeterminado si no está definido.
$nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Usuario';

// Prepara y ejecuta una consulta para obtener las solicitudes de ausencia del usuario.
$consulta_ausencias = $conexion->prepare("SELECT * FROM ausencias WHERE usuario_id = ?");
$consulta_ausencias->bind_param("i", $usuario_id);
$consulta_ausencias->execute();
$resultado_ausencias = $consulta_ausencias->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Ausencias</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">

</head>

<body>
    <div class="container-grande">
        <div class="logo">
            <img src="img/logo.png" alt="Logo de la empresa"> <!-- Muestra el logo de la empresa -->
        </div>
        <div class="contenido">
            <h1>Solicitud de Ausencias</h1>
            <h1>Bienvenido,
                <?php echo htmlspecialchars($nombre_usuario); ?>
            </h1> <!-- Saludo al usuario con escape de caracteres para prevenir XSS -->

            <!-- Mensajes de notificación de éxito o error -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="mensaje-exito">
                    <?php echo $_SESSION['mensaje'];
                    unset($_SESSION['mensaje']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mensaje-error">
                    <?php echo $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Enlace para volver a la página principal -->
            <div class="volver"><a href="paginaTrabajador.php" class="boton">Volver a la página principal</a>

                <!-- Formulario para enviar una nueva solicitud de ausencia -->
                <h2>Enviar Solicitud de Ausencia</h2>
                <form action="ausencias.php" method="post">
                    <label for="tipo_ausencia">Tipo de Ausencia:</label>
                    <select name="tipo_ausencia" id="tipo_ausencia" required>
                        <option value="Vacaciones">Vacaciones</option>
                        <option value="Ausencia Justificada">Ausencia Justificada</option>
                    </select><br>
                    <label for="fecha_inicio">Fecha de Inicio:</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" required><br>
                    <label for="fecha_fin">Fecha de Fin:</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" required><br>
                    <label for="motivo">Motivo:</label><br>
                    <textarea name="motivo" id="motivo" rows="4" cols="50" required></textarea><br>
                    <button type="submit" class="boton width">Enviar Solicitud</button>
                </form>

                <!-- Sección para mostrar las solicitudes de ausencia previas del usuario -->
                <h2>Mis Solicitudes de Ausencia</h2>
                <table>
                    <tr>
                        <th>Tipo</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Fin</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                    </tr>
                    <?php while ($fila = $resultado_ausencias->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($fila['tipo_ausencia']); ?>
                            </td>
                            <td>
                                <?php echo date('d-m-Y', strtotime($fila['fecha_inicio'])); ?>
                            </td>
                            <td>
                                <?php echo date('d-m-Y', strtotime($fila['fecha_fin'])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($fila['motivo']); ?>
                            </td>
                            <td
                                class="<?php echo $fila['estado'] === 'Aceptada' ? 'estado-aceptada' : ($fila['estado'] === 'Rechazada' ? 'estado-rechazada' : ''); ?>">
                                <?php echo htmlspecialchars($fila['estado']); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        <footer>
            <div class="legal-info">
                <p>© 2024 Cliwo. Click and Work. Todos los derechos reservados.</p>
            </div>
        </footer>
</body>

</html>
<?php $conexion->close(); ?> <!-- Cierra la conexión a la base de datos -->