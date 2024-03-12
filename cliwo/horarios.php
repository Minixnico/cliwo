<?php
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
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

    // Antes de preparar la inserción, verifica si ya existe un registro para evitar duplicados
    $consultaDuplicado = $conexion->prepare("SELECT id FROM horarios WHERE usuario_id = ? AND dia_semana = ? AND turno = ?");
    $consultaDuplicado->bind_param("iss", $usuario_id, $dia_semana, $turno_seleccionado);
    $consultaDuplicado->execute();
    $resultadoDuplicado = $consultaDuplicado->get_result();

    if ($resultadoDuplicado->num_rows > 0) {
        // Si ya existe un registro, evita la inserción y muestra un mensaje de error.
        $_SESSION['error'] = "Ya existe un turno asignado para este usuario en la fecha y turno seleccionados.";
        header("Location: horarios.php");
        exit;
    } else {
        // Si no hay duplicados, procede con la inserción.
        $consulta = $conexion->prepare("INSERT INTO horarios (usuario_id, dia_semana, turno, hora_entrada, hora_salida) VALUES (?, ?, ?, ?, ?)");
        $consulta->bind_param("issss", $usuario_id, $dia_semana, $turno_seleccionado, $hora_entrada, $hora_salida);
        if (!$consulta->execute()) {
            $_SESSION['error'] = "Error al asignar el horario: " . $consulta->error;
        } else {
            $_SESSION['mensaje'] = "Horario asignado correctamente.";
        }
        header("Location: horarios.php");
        exit;
    }

}

// Borrado de horarios
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

// Obtener la lista de trabajadores
$listaTrabajadores = [];
$resultado = $conexion->query("SELECT id, CONCAT(nombre, ' ', apellidos) AS nombre_completo FROM trabajadores");
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $listaTrabajadores[] = $fila;
    }
} else {
    echo "Error al obtener la lista de trabajadores: " . $conexion->error;
}

// Filtrado de horarios por usuario, mes y año
$usuario_id_filtrado = isset($_GET['usuario_id_filtrado']) && $_GET['usuario_id_filtrado'] !== '' ? $_GET['usuario_id_filtrado'] : null;
$mes_filtrado = $_GET['mes_filtrado'] ?? date('m');
$anio_filtrado = $_GET['anio_filtrado'] ?? date('Y');

$sqlFiltrado = "SELECT h.id, u.Nombre_Completo, h.dia_semana, h.turno, h.hora_entrada, h.hora_salida
                FROM horarios h
                JOIN usuarios u ON h.usuario_id = u.id
                WHERE MONTH(h.dia_semana) = ? AND YEAR(h.dia_semana) = ?";
if ($usuario_id_filtrado) {
    $sqlFiltrado .= " AND h.usuario_id = ?";
}

$stmt = $conexion->prepare($sqlFiltrado);

if ($usuario_id_filtrado) {
    $stmt->bind_param("iii", $mes_filtrado, $anio_filtrado, $usuario_id_filtrado);
} else {
    $stmt->bind_param("ii", $mes_filtrado, $anio_filtrado);
}

$stmt->execute();
$resultado_horarios = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asignación de Horarios</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="registro">
        <div class="logo"><img src="img/logo.png" alt="Logo"></div>
        <h1>Asignación de Horarios a Trabajadores</h1>


    <!-- Aquí es donde mostramos los mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <p class="mensaje-exito">
            <?= $_SESSION['mensaje']; ?>
            <?php unset($_SESSION['mensaje']); ?>
        </p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <p class="mensaje-error">
            <?= $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </p>
    <?php endif; ?>

        <div class="volver"><a href="paginaprincipal.php" class="boton">Volver a la página principal</a></div>

        <form action="horarios.php" method="post">
            <select name="usuario_id" required>
                <option value="">Seleccione un trabajador</option>
                <?php
                $resultado = $conexion->query("SELECT id, Nombre_Completo FROM usuarios");
                if ($resultado) {
                    while ($fila = $resultado->fetch_assoc()) {
                        echo "<option value=\"{$fila['id']}\">{$fila['Nombre_Completo']}</option>";
                    }
                }
                ?>
            </select>
            <input type="date" name="dia_semana" required>
            <select name="turno" required>
                <option value="">Seleccione un turno</option>
                <?php foreach ($turnos as $nombre => $detalle): ?>
                    <option value="<?= $nombre; ?>">
                        <?= $nombre; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="boton">Asignar Horario</button>
        </form>

        <h2>Horarios Asignados</h2>
        <form action="horarios.php" method="get">
            <!-- Cambio de 'trabajador_id_filtrado' a 'usuario_id_filtrado' -->
            <select name="usuario_id_filtrado">
                <option value="">Todos los trabajadores</option>
                <?php
                $usuarios = $conexion->query("SELECT id, Nombre_Completo FROM usuarios");
                while ($usuario = $usuarios->fetch_assoc()) {
                    echo "<option value=\"{$usuario['id']}\">{$usuario['Nombre_Completo']}</option>";
                }
                ?>
            </select>
            <select name="mes_filtrado" required>
                <option value="">Seleccione un mes</option>
                <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                    <option value="<?= $mes; ?>" <?= $mes_filtrado == $mes ? 'selected' : ''; ?>>
                        <?= strftime('%B', mktime(0, 0, 0, $mes, 10)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="anio_filtrado" required>
                <option value="">Seleccione un año</option>
                <?php for ($anio = date('Y'); $anio >= date('Y') - 5; $anio--): ?>
                    <option value="<?= $anio; ?>" <?= $anio_filtrado == $anio ? 'selected' : ''; ?>>
                        <?= $anio; ?>
                    </option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="boton">Filtrar Horarios</button>
        </form>

        <table>
            <tr>
            <tr>
                <th>Nombre Completo</th>
                <th>Día</th>
                <th>Turno</th>
                <th>Hora de Entrada</th>
                <th>Hora de Salida</th>
                <th>Acciones</th>
            </tr>
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
                        </td>
                        <td><a class="boton" href="horarios.php?borrar_id=<?= $fila['id']; ?>"
                                onclick="return confirm('¿Estás seguro de querer borrar este horario?');">Borrar</a></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No se encontraron horarios.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>

</html>