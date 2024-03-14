<?php
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');
// Establece la configuración regional para asegurarse de que las fechas se muestren en español.

session_start();
// Inicia una nueva sesión o reanuda la existente.

// Verifica si el usuario está logueado redirigiéndolo a la página de inicio de sesión si no lo está.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Establece la conexión con la base de datos.
$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    // Termina la ejecución del script si hay un error de conexión.
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtiene el ID del usuario de la sesión, asumiendo que se ha guardado al momento del login.
$usuario_id = $_SESSION['usuario_id'];

// Obtiene el mes y el año filtrados desde la URL, o usa el mes y año actual como predeterminado.
$mes_filtrado = isset($_GET['mes_filtrado']) ? $_GET['mes_filtrado'] : date('m');
$anio_filtrado = isset($_GET['anio_filtrado']) ? $_GET['anio_filtrado'] : date('Y');

// Prepara una consulta SQL para obtener los horarios del trabajador logueado filtrados por mes y año.
$sql = "SELECT id, dia_semana, turno, hora_entrada, hora_salida FROM horarios 
        WHERE usuario_id = ? AND MONTH(dia_semana) = ? AND YEAR(dia_semana) = ?
        ORDER BY dia_semana, hora_entrada";

// Prepara la consulta SQL con los parámetros proporcionados.
$consulta = $conexion->prepare($sql);
// Vincula los parámetros a la consulta.
$consulta->bind_param('iii', $usuario_id, $mes_filtrado, $anio_filtrado);
// Ejecuta la consulta.
$consulta->execute();
// Obtiene el resultado de la consulta.
$resultado_horarios = $consulta->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <!-- Define la codificación de caracteres para el contenido -->
    <title>Horario del Trabajador</title>
    <!-- Título que aparecerá en la pestaña del navegador -->
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>
    <div class="container-grande">
        <div class="registro">
            <!-- Contenedor principal para el contenido de la página -->
            <div class="logo"><img src="img/logo.png" alt="Logo"></div>
            <!-- Muestra el logo de la empresa o aplicación -->
        </div>
        <!-- Enlace para volver a la página principal del trabajador -->
        <a href="paginaTrabajador.php" class="boton">Volver a la página principal</a>
        <div class="container-grande" <h1>Bienvenido,
            <?php echo $_SESSION['nombre_usuario']; ?>
            </h1>
            <!-- Saluda al usuario con el nombre almacenado en la sesión -->

            <h1>Horario del Trabajador</h1>
            <!-- Título de la sección -->

            <!-- Formulario para filtrar los horarios por mes y año -->
            <form action="horarioTrabajador.php" method="get">
                <label for="mes_filtrado">Mes:</label>
                <select name="mes_filtrado" id="mes_filtrado">
                    <!-- Genera opciones de mes usando un bucle, seleccionando el mes actual por defecto -->
                    <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                        <option value="<?= $mes; ?>" <?= $mes == $mes_filtrado ? 'selected' : ''; ?>>
                            <?= strftime('%B', mktime(0, 0, 0, $mes, 10)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <label for="anio_filtrado">Año:</label>
                <select name="anio_filtrado" id="anio_filtrado">
                    <!-- Genera opciones de año, mostrando los últimos 10 años -->
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

            <!-- Tabla que muestra los horarios filtrados -->
            <table>
                <thead>
                    <tr>
                        <!-- Cabeceras de las columnas de la tabla -->
                        <th>ID</th>
                        <th>Día</th>
                        <th>Turno</th>
                        <th>Hora de Entrada</th>
                        <th>Hora de Salida</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Verifica si hay horarios disponibles y los muestra -->
                    <?php if ($resultado_horarios->num_rows > 0): ?>
                        <?php while ($fila = $resultado_horarios->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= $fila['id']; ?>
                                </td>
                                <td>
                                    <?= strftime('%d/%m/%Y', strtotime($fila['dia_semana'])); ?>
                                </td>
                                <td>
                                    <?= $fila['turno']; ?>
                                </td>
                                <td>
                                    <?= $fila['hora_entrada'] ?: 'N/A'; ?>
                                </td>
                                <td>
                                    <?= $fila['hora_salida'] ?: 'N/A'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Muestra un mensaje si no se encuentran horarios -->
                        <tr>
                            <td colspan="5">No se encontraron horarios para los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
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