<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_id'] != 1) {
    header("Location: login.php");
    exit;
}

$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Procesar acciones de aceptar o rechazar
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $accion = $_GET['action'];

    if ($accion === 'aceptar' || $accion === 'rechazar') {
        $conexion->begin_transaction();
        try {
            $nuevoEstado = $accion === 'aceptar' ? 'Aceptada' : 'Rechazada';
            // Actualizar el estado de la solicitud
            $consultaActualizarEstado = $conexion->prepare("UPDATE ausencias SET estado = ? WHERE id = ?");
            $consultaActualizarEstado->bind_param("si", $nuevoEstado, $id);
            $consultaActualizarEstado->execute();

            // Si la solicitud es aceptada, actualizar los horarios
            if ($accion === 'aceptar') {
                $consultaDetalles = $conexion->prepare("SELECT usuario_id, fecha_inicio, fecha_fin FROM ausencias WHERE id = ?");
                $consultaDetalles->bind_param("i", $id);
                $consultaDetalles->execute();
                $resultadoDetalles = $consultaDetalles->get_result();
                $detalle = $resultadoDetalles->fetch_assoc();

                if ($detalle) {
                    $inicio = new DateTime($detalle['fecha_inicio']);
                    $fin = new DateTime($detalle['fecha_fin']);
                    $fin = $fin->modify('+1 day');

                    $interval = new DateInterval('P1D');
                    $periodo = new DatePeriod($inicio, $interval, $fin);

                    foreach ($periodo as $dia) {
                        // Asegúrate de que aquí se usa 'usuario_id' en lugar de 'trabajador_id'
                        $consultaInsertarHorario = $conexion->prepare("INSERT INTO horarios (usuario_id, dia_semana, turno) VALUES (?, ?, 'Ausencia')");
                        $diaSemana = $dia->format("Y-m-d");
                        // Como ya no se utiliza 'trabajador_id', solo pasamos 'usuario_id' y 'diaSemana' como parámetros
                        $consultaInsertarHorario->bind_param("is", $detalle['usuario_id'], $diaSemana);
                        $consultaInsertarHorario->execute();
                    }
                }
            }

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

// Obtener solicitudes pendientes con el nombre del usuario
$solicitudesPendientes = $conexion->query("
    SELECT ausencias.id, usuarios.Nombre_Completo, ausencias.tipo_ausencia, ausencias.fecha_inicio, ausencias.fecha_fin, ausencias.estado 
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
</head>

<body>
    <div class="logo">
        <img src="img/logo.png" alt="Logo de CLIWO">
    </div>
    <div class="volver"><a href="paginaPrincipal.php" class="boton width">Volver a la página principal</a>

        <div class="container">
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
        <th>Acciones</th>
    </tr>
    <?php while ($fila = $solicitudesPendientes->fetch_assoc()): ?>
        <tr>
            <td><?php echo $fila['id']; ?></td>
            <td><?php echo $fila['Nombre_Completo']; ?></td>
            <td><?php echo $fila['tipo_ausencia']; ?></td>
            <td><?php echo $fila['fecha_inicio']; ?></td>
            <td><?php echo $fila['fecha_fin']; ?></td>
            <td><?php echo $fila['estado']; ?></td>
            <td>
                <a class="boton" href="gestionarAusencias.php?action=aceptar&id=<?php echo $fila['id']; ?>">Aceptar</a> 
                <a class="boton" href="gestionarAusencias.php?action=rechazar&id=<?php echo $fila['id']; ?>">Rechazar</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
        </div>
</body>

</html>
<?php
$conexion->close();
?>