<?php
session_start();
setlocale(LC_TIME, 'es_ES', 'Spanish_Spain', 'Spanish');
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

$turnos = [
    "Mañana" => ["hora_entrada" => "08:00", "hora_salida" => "15:00"],
    "Tarde" => ["hora_entrada" => "15:00", "hora_salida" => "22:00"],
    "Noche" => ["hora_entrada" => "22:00", "hora_salida" => "08:00"],
    "Vacaciones" => ["hora_entrada" => null, "hora_salida" => null],
    "Ausencia Justificada" => ["hora_entrada" => null, "hora_salida" => null]
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'], $_POST['dia_semana'], $_POST['turno'])) {
    $usuario_id = $_POST['usuario_id'];
    $fecha = DateTime::createFromFormat('Y-m-d', $_POST['dia_semana']);
    $dia_semana = $fecha ? $fecha->format('Y-m-d') : null;
    $turno_seleccionado = $_POST['turno'];

    $hora_entrada = $turnos[$turno_seleccionado]['hora_entrada'];
    $hora_salida = $turnos[$turno_seleccionado]['hora_salida'];

    $consultaDuplicado = $conexion->prepare("SELECT id FROM horarios WHERE usuario_id = ? AND dia_semana = ? AND turno = ?");
    $consultaDuplicado->bind_param("iss", $usuario_id, $dia_semana, $turno_seleccionado);
    $consultaDuplicado->execute();
    $resultadoDuplicado = $consultaDuplicado->get_result();

    if ($resultadoDuplicado->num_rows > 0) {
        $_SESSION['error'] = "Ya existe un turno asignado para este usuario en la fecha y turno seleccionados.";
    } else {
        $consulta = $conexion->prepare("INSERT INTO horarios (usuario_id, dia_semana, turno, hora_entrada, hora_salida) VALUES (?, ?, ?, ?, ?)");
        $consulta->bind_param("issss", $usuario_id, $dia_semana, $turno_seleccionado, $hora_entrada, $hora_salida);
        if ($consulta->execute()) {
            $_SESSION['mensaje'] = "Horario asignado correctamente.";
        } else {
            $_SESSION['error'] = "Error al asignar el horario: " . $consulta->error;
        }
    }
    header("Location: horarios.php");
    exit;
}

if (isset($_GET['borrar_id'])) {
    $borrar_id = $_GET['borrar_id'];
    $consultaBorrar = $conexion->prepare("DELETE FROM horarios WHERE id = ?");
    $consultaBorrar->bind_param("i", $borrar_id);

    if ($consultaBorrar->execute()) {
        $_SESSION['mensaje'] = "Horario borrado correctamente.";
    } else {
        $_SESSION['error'] = "Error al borrar el horario: " . $consultaBorrar->error;
    }
    header("Location: horarios.php");
    exit;
}

$mes_filtrado = $_GET['mes_filtrado'] ?? date('m');
$anio_filtrado = $_GET['anio_filtrado'] ?? date('Y');

$sqlFiltrado = "SELECT h.id, u.Nombre_Completo, h.dia_semana, h.turno, h.hora_entrada, h.hora_salida
                FROM horarios h
                JOIN usuarios u ON h.usuario_id = u.id
                WHERE MONTH(h.dia_semana) = ? AND YEAR(h.dia_semana) = ?";
if (isset($_GET['usuario_id_filtrado']) && $_GET['usuario_id_filtrado'] !== '') {
    $sqlFiltrado .= " AND h.usuario_id = ?";
    $stmt = $conexion->prepare($sqlFiltrado);
    $stmt->bind_param("iii", $mes_filtrado, $anio_filtrado, $_GET['usuario_id_filtrado']);
} else {
    $stmt = $conexion->prepare($sqlFiltrado);
    $stmt->bind_param("ii", $mes_filtrado, $anio_filtrado);
}
$stmt->execute();
$resultado_horarios = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <!-- Establece la codificación de caracteres para el contenido -->
    <title>Asignación de Horarios</title>
    <!-- Título que se muestra en la pestaña del navegador -->
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>

    <div class="container-grande">
        <!-- Contenedor principal para la asignación de horarios -->
        <div class="logo"><img src="img/logo.png" alt="Logo"></div>
        <!-- Muestra el logo de la empresa -->
        <h1>Asignación de Horarios a Trabajadores</h1>
        <!-- Título de la sección -->

        <!-- Sección para mostrar mensajes de éxito o error -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <!-- Si hay un mensaje de éxito, lo muestra y luego lo elimina de la sesión -->
            <p class="mensaje-exito">
                <?= $_SESSION['mensaje']; ?>
                <?php unset($_SESSION['mensaje']); ?>
            </p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <!-- Si hay un mensaje de error, lo muestra y luego lo elimina de la sesión -->
            <p class="mensaje-error">
                <?= $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </p>
        <?php endif; ?>

        <!-- Enlace para volver a la página principal -->
        <div class="volver"><a href="paginaprincipal.php" class="boton">Volver a la página principal</a></div>

        <!-- Formulario para asignar horarios a los trabajadores -->
        <form action="horarios.php" method="post">
            <!-- Selector de trabajador -->
            <select name="usuario_id" required>
                <option value="">Seleccione un trabajador</option>
                <?php
                // Consulta a la base de datos para obtener la lista de trabajadores
                $resultado = $conexion->query("SELECT id, Nombre_Completo FROM usuarios");
                if ($resultado) {
                    while ($fila = $resultado->fetch_assoc()) {
                        echo "<option value=\"{$fila['id']}\">{$fila['Nombre_Completo']}</option>";
                    }
                }
                ?>
            </select>
            <!-- Campo para seleccionar la fecha -->
            <input type="date" name="dia_semana" required>
            <!-- Selector de turno -->
            <select name="turno" required>
                <option value="">Seleccione un turno</option>
                <?php foreach ($turnos as $nombre => $detalle): ?>
                    <option value="<?= $nombre; ?>">
                        <?= $nombre; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- Botón para enviar el formulario y asignar el horario -->
            <button type="submit" class="boton">Asignar Horario</button>
        </form>

        <!-- Título para la sección de horarios asignados -->
        <h2>Horarios Asignados</h2>
        <!-- Formulario para filtrar los horarios por trabajador, mes y año -->
        <form action="horarios.php" method="get">
            <select name="usuario_id_filtrado">
                <option value="">Todos los trabajadores</option>
                <?php
                // Consulta para obtener la lista de todos los usuarios
                $usuarios = $conexion->query("SELECT id, Nombre_Completo FROM usuarios");
                while ($usuario = $usuarios->fetch_assoc()) {
                    echo "<option value=\"{$usuario['id']}\">{$usuario['Nombre_Completo']}</option>";
                }
                ?>
            </select>
            <!-- Selector de mes -->
            <select name="mes_filtrado" required>
                <option value="">Seleccione un mes</option>
                <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                    <option value="<?= $mes; ?>" <?= $mes_filtrado == $mes ? 'selected' : ''; ?>>
                        <?= strftime('%B', mktime(0, 0, 0, $mes, 10)); // Muestra el nombre del mes en español     ?>
                    </option>
                <?php endfor; ?>
            </select>
            <!-- Selector de año -->
            <select name="anio_filtrado" required>
                <option value="">Seleccione un año</option>
                <?php for ($anio = date('Y'); $anio >= date('Y') - 5; $anio--): ?>
                    <option value="<?= $anio; ?>" <?= $anio_filtrado == $anio ? 'selected' : ''; ?>>
                        <?= $anio; ?>
                    </option>
                <?php endfor; ?>
            </select>
            <!-- Botón para aplicar los filtros -->
            <button type="submit" class="boton">Filtrar Horarios</button>
        </form>

        <!-- Tabla que muestra los horarios asignados según los filtros aplicados -->
        <table>
            <tr>
                <th>Nombre Completo</th>
                <th>Día</th>
                <th>Turno</th>
                <th>Hora de Entrada</th>
                <th>Hora de Salida</th>
                <th>Acciones</th>
            </tr>
            <?php if ($resultado_horarios && $resultado_horarios->num_rows > 0): ?>
                <?php while ($fila = $resultado_horarios->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($fila['Nombre_Completo']); ?>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($fila['dia_semana'])); ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($fila['turno']); ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($fila['hora_entrada']); ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($fila['hora_salida']); ?>
                        </td>
                        <td><a class="boton" href="horarios.php?borrar_id=<?= $fila['id']; ?>"
                                onclick="return confirm('¿Estás seguro de querer borrar este horario?');">Borrar</a></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Mensaje mostrado si no hay horarios que cumplan con los criterios de filtro -->
                <tr>
                    <td colspan="7">No se encontraron horarios.</td>
                </tr>
            <?php endif; ?>
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