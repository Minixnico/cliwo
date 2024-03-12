<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$usuario_id = $_SESSION['usuario_id']; // Obtener el ID del usuario de la sesión

// Procesar formulario de solicitud de ausencia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tipo_ausencia'], $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['motivo'])) {
    $tipo_ausencia = $_POST['tipo_ausencia'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $motivo = $_POST['motivo'];

    // Validación de fechas en el servidor
    if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        $_SESSION['error'] = "La fecha de inicio debe ser anterior a la fecha de fin.";
        header("Location: ausencias.php");
        exit;
    }

    // Insertar solicitud en la base de datos
// Insertar solicitud en la base de datos
    $consulta = $conexion->prepare("INSERT INTO ausencias (usuario_id, tipo_ausencia, fecha_inicio, fecha_fin, motivo, estado) VALUES (?, ?, ?, ?, ?, 'Pendiente')");
    $consulta->bind_param("issss", $usuario_id, $tipo_ausencia, $fecha_inicio, $fecha_fin, $motivo);
    if ($consulta->execute()) {
        $_SESSION['mensaje'] = "Solicitud de ausencia enviada correctamente. Esperando aprobación.";
    } else {
        $_SESSION['error'] = "Error al enviar la solicitud de ausencia: " . $consulta->error;
    }

    header("Location: ausencias.php");
    exit;
}

// Obtener información del usuario
// Asegúrate de haber almacenado el nombre del usuario en la sesión al iniciar sesión
$nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Usuario'; // Utiliza 'Usuario' como valor por defecto si no está definido

// Obtener lista de solicitudes de ausencia del usuario
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
    <!-- Incluir aquí cualquier otro archivo CSS o JS necesario -->
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.png" alt="Logo de la empresa">
        </div>
        <div class="contenido">
            <h1>Solicitud de Ausencias</h1>
            <h1>Bienvenido,
                <?php echo htmlspecialchars($nombre_usuario); ?>
            </h1>

            <!-- Mensajes de notificación -->
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
            <div class="volver"><a href="paginaTrabajador.php" class="boton width">Volver a la página principal</a>

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
                    <button type="submit" class="boton">Enviar Solicitud</button>
                </form>

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
</body>

</html>
<?php $conexion->close(); ?>