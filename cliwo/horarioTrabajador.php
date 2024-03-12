<?php
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');
session_start();
// Redirige si el usuario no está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Asumiendo que guardas el id del trabajador en $_SESSION al momento del login
$usuario_id = $_SESSION['usuario_id'];

$mes_filtrado = isset($_GET['mes_filtrado']) ? $_GET['mes_filtrado'] : date('m'); // Mes actual por defecto
$anio_filtrado = isset($_GET['anio_filtrado']) ? $_GET['anio_filtrado'] : date('Y'); // Año actual por defecto

// Consulta para obtener los horarios del trabajador logueado, filtrados por mes y año
$sql = "SELECT id, dia_semana, turno, hora_entrada, hora_salida FROM horarios 
        WHERE usuario_id = ? AND MONTH(dia_semana) = ? AND YEAR(dia_semana) = ?
        ORDER BY dia_semana, hora_entrada";

$consulta = $conexion->prepare($sql);
$consulta->bind_param('iii', $usuario_id, $mes_filtrado, $anio_filtrado);
$consulta->execute();
$resultado_horarios = $consulta->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horario del Trabajador</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="registro">
    <div class="logo"><img src="img/logo.png" alt="Logo"></div>
    <div class="volver"><a href="paginaTrabajador.php" class="boton">Volver a la página principal</a>
</div>
<h1>Bienvenido,
        <?php echo $_SESSION['nombre_usuario']; ?>
    </h1>
        <h1>Horario del Trabajador</h1>
        <form action="horarioTrabajador.php" method="get">
            <label for="mes_filtrado">Mes:</label>
            <select name="mes_filtrado" id="mes_filtrado">
                <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                <option value="<?= $mes; ?>" <?= $mes == $mes_filtrado ? 'selected' : ''; ?>>
                    <?= strftime('%B', mktime(0, 0, 0, $mes, 10)); ?>
                </option>
                <?php endfor; ?>
            </select>
            <label for="anio_filtrado">Año:</label>
            <select name="anio_filtrado" id="anio_filtrado">
                <?php
                $anio_actual = date("Y");
                for ($anio = $anio_actual; $anio >= $anio_actual - 10; $anio--): ?>
                <option value="<?= $anio; ?>" <?= $anio == $anio_filtrado ? 'selected' : ''; ?>>
                    <?= $anio; ?>
                </option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="boton">Filtrar Horarios</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Día</th>
                    <th>Turno</th>
                    <th>Hora de Entrada</th>
                    <th>Hora de Salida</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_horarios->num_rows > 0): ?>
                    <?php while ($fila = $resultado_horarios->fetch_assoc()): ?>
                        <tr>
                            <td><?= $fila['id']; ?></td>
                            <td><?= strftime('%d/%m/%Y', strtotime($fila['dia_semana'])); ?></td>
                            <td><?= $fila['turno']; ?></td>
                            <td><?= $fila['hora_entrada'] ?: 'N/A'; ?></td>
                            <td><?= $fila['hora_salida'] ?: 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No se encontraron horarios para los filtros seleccionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conexion->close(); ?>
