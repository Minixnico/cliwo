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

$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $accion = $_GET['action'];

    if ($accion === 'aceptar' || $accion === 'rechazar') {
        $conexion->begin_transaction();
        try {
            $nuevoEstado = $accion === 'aceptar' ? 'Aceptada' : 'Rechazada';
            $consultaActualizarEstado = $conexion->prepare("UPDATE ausencias SET estado = ? WHERE id = ?");
            $consultaActualizarEstado->bind_param("si", $nuevoEstado, $id);
            $consultaActualizarEstado->execute();

            $conexion->commit();
            $_SESSION['mensaje'] = "La solicitud ha sido " . ($accion === 'aceptar' ? "aceptada" : "rechazada") . " correctamente.";
        } catch (Exception $e) {
            $conexion->rollback();
            $_SESSION['error'] = "Error al procesar la solicitud: " . $e->getMessage();
        }

        header("Location: gestionarAusencias.php");
        exit;
    } else {
        $_SESSION['error'] = "Acción no válida.";
        header("Location: gestionarAusencias.php");
        exit;
    }
}

$solicitudesPendientes = $conexion->query("
    SELECT ausencias.id, usuarios.Nombre_Completo, ausencias.tipo_ausencia, ausencias.fecha_inicio, ausencias.fecha_fin, ausencias.estado, ausencias.motivo 
    FROM ausencias 
    JOIN usuarios ON ausencias.usuario_id = usuarios.id 
    WHERE ausencias.estado = 'Pendiente'
");

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Ausencias</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>
    <div class="container-grande">
        <div class="logo">
            <img src="img/logo.png" alt="Logo de CLIWO">
        </div>
        <div class="volver">
            <a href="paginaPrincipal.php" class="boton width">Volver a la página principal</a>
        </div>
        <div>
            <h1>Gestionar Solicitudes de Ausencia</h1>
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
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Trabajador</th>
                    <th>Tipo de Ausencia</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Fin</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                    <th>Acciones</th>
                </tr>
                <?php while ($fila = $solicitudesPendientes->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php echo $fila['id']; ?>
                        </td>
                        <td>
                            <?php echo $fila['Nombre_Completo']; ?>
                        </td>
                        <td>
                            <?php echo $fila['tipo_ausencia']; ?>
                        </td>
                        <td>
                            <?php echo date('d-m-Y', strtotime($fila['fecha_inicio'])); ?>
                        </td>
                        <td>
                            <?php echo date('d-m-Y', strtotime($fila['fecha_fin'])); ?>
                        </td>
                        <td>
                            <?php echo $fila['estado']; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($fila['motivo']); ?>
                        </td>
                        <td>
                            <a class="boton"
                                href="gestionarAusencias.php?action=aceptar&id=<?php echo $fila['id']; ?>">Aceptar</a>
                            <a class="boton"
                                href="gestionarAusencias.php?action=rechazar&id=<?php echo $fila['id']; ?>">Rechazar</a>
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
<?php $conexion->close(); ?>