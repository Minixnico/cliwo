<?php
session_start(); // Inicia una nueva sesión o reanuda la existente.

// Establece una conexión con la base de datos MySQL.
$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    // Si hay un error en la conexión, termina la ejecución del script mostrando el mensaje de error.
    die("Error de conexión: " . $conexion->connect_error);
}

// Verifica si el usuario ha iniciado sesión; si no, redirige a la página de inicio de sesión.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id']; // Obtiene el ID del usuario de la sesión.

// Si el usuario es administrador (usuario_id = 1), obtiene la lista de todos los trabajadores.
$listaTrabajadores = [];
if ($usuario_id == 1) {
    // Ejecuta una consulta para obtener los ID y nombres completos de los usuarios.
    $resultado = $conexion->query("SELECT id, Nombre_Completo FROM usuarios ORDER BY Nombre_Completo");
    while ($fila = $resultado->fetch_assoc()) {
        // Almacena cada trabajador en el array asociativo $listaTrabajadores.
        $listaTrabajadores[$fila['id']] = $fila['Nombre_Completo'];
    }
}

// Determina el ID del usuario cuyos fichajes se mostrarán.
$usuario_id_mostrar = $usuario_id; // Por defecto, muestra los fichajes del usuario actual.
if (isset($_POST['usuario_id']) && array_key_exists($_POST['usuario_id'], $listaTrabajadores)) {
    // Si el administrador ha seleccionado otro usuario, actualiza $usuario_id_mostrar.
    $usuario_id_mostrar = $_POST['usuario_id'];
}

// Prepara una consulta para verificar el último fichaje del usuario seleccionado.
$consulta_ultimo_fichaje = $conexion->prepare("SELECT hora_salida FROM fichajes WHERE usuario_id = ? ORDER BY hora_entrada DESC LIMIT 1");
$consulta_ultimo_fichaje->bind_param("i", $usuario_id_mostrar);
$consulta_ultimo_fichaje->execute();
$resultado_ultimo_fichaje = $consulta_ultimo_fichaje->get_result();
$ultimo_fichaje = $resultado_ultimo_fichaje->fetch_assoc();

// Determina si debe mostrarse el botón de salida basado en si el último fichaje tiene o no una hora de salida.
$mostrar_boton_salida = false;
if ($ultimo_fichaje && is_null($ultimo_fichaje['hora_salida'])) {
    $mostrar_boton_salida = true;
}

// Procesa las acciones de fichaje (entrada o salida) para el usuario actual.
if (isset($_POST['accion']) && $usuario_id == $_SESSION['usuario_id']) {
    // Obtiene la acción solicitada (entrada o salida).
    $accion = $_POST['accion'];
    $consulta = null;

    // Prepara la consulta adecuada según la acción.
    if ($accion == 'entrada') {
        // Inserta un nuevo registro de fichaje con la hora actual como hora de entrada.
        $consulta = $conexion->prepare("INSERT INTO fichajes (usuario_id, hora_entrada) VALUES (?, NOW())");
    } elseif ($accion == 'salida') {
        // Actualiza el último fichaje sin hora de salida, estableciendo la hora actual como hora de salida.
        $consulta = $conexion->prepare("UPDATE fichajes SET hora_salida = NOW() WHERE usuario_id = ? AND hora_salida IS NULL ORDER BY hora_entrada DESC LIMIT 1");
    }

    // Ejecuta la consulta y muestra un mensaje de éxito o error.
    if ($consulta && $consulta->bind_param("i", $usuario_id) && $consulta->execute()) {
        $_SESSION['mensaje'] = "Fichaje de $accion realizado correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al realizar el fichaje.";
    }

    // Redirige a la misma página para evitar reenvíos del formulario.
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Prepara y ejecuta una consulta para obtener todos los fichajes del usuario seleccionado.
$consulta_fichajes = $conexion->prepare("SELECT id, hora_entrada, hora_salida FROM fichajes WHERE usuario_id = ? ORDER BY hora_entrada DESC");
$consulta_fichajes->bind_param("i", $usuario_id_mostrar);
$consulta_fichajes->execute();
$resultado_fichajes = $consulta_fichajes->get_result();
?>
<!DOCTYPE html>
<!-- Indica que el documento es de tipo HTML5 -->
<html lang="es">
<!-- Establece el idioma del documento en español -->

<head>
    <meta charset="UTF-8">
    <!-- Define la codificación de caracteres como UTF-8 -->
    <title>Fichajes</title>
    <!-- Título que aparecerá en la pestaña del navegador -->
    <link rel="stylesheet" href="styles.css" />
    <!-- Vincula un archivo CSS externo para estilizar la página -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.png" />
            <!-- Muestra el logo de la empresa o aplicación -->
        </div>
        <h1>Bienvenido,
            <?php echo $_SESSION['nombre_usuario']; ?>
        </h1>
        <!-- Saluda al usuario con el nombre almacenado en la sesión -->

        <!-- Muestra mensajes de éxito o error almacenados en la sesión -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje-exito">
                <p>
                    <?php echo $_SESSION['mensaje']; ?>
                </p>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <!-- Formulario para fichar entrada o salida -->
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="centrar">
            <!-- El formulario cambia dinámicamente según si el usuario debe fichar entrada o salida -->
            <?php if ($mostrar_boton_salida): ?>
                <!-- Si el usuario ya ha fichado entrada pero no salida, muestra botón de salida -->
                <input type="hidden" name="accion" value="salida">
                <button type="submit" class="boton-fichaje"><img src="img/salida.png" class="imagen-reducida">Fichar
                    Salida</button>
            <?php else: ?>
                <!-- De lo contrario, muestra botón de entrada -->
                <input type="hidden" name="accion" value="entrada">
                <button type="submit" class="boton-fichaje"><img src="img/entrada.png" class="imagen-reducida">Fichar
                    Entrada</button>
            <?php endif; ?>
        </form>

        <!-- Selector de trabajadores solo visible para el administrador -->
        <?php if ($usuario_id == 1): ?>
            <!-- Si el usuario es administrador, puede seleccionar un trabajador para ver sus fichajes -->
            <a href="paginaPrincipal.php" class="boton">Volver a la página principal</a>
            <h2>Registros de Fichajes</h2>
            <p class="texto">Seleccione un trabajador para ver su registro de fichajes</p>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <select name="usuario_id">
                    <!-- Muestra una lista desplegable con los trabajadores -->
                    <?php foreach ($listaTrabajadores as $id => $nombre): ?>
                        <option value="<?php echo $id; ?>" <?php echo $id == $usuario_id_mostrar ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="boton width">Ver Fichajes</button>
            </form>
        <?php else: ?>
            <a href="paginatrabajador.php" class="boton">Volver a la página principal</a>
            <h2>Registros de Fichajes</h2>
        <?php endif; ?>

        <!-- Tabla que muestra los fichajes del usuario (o del seleccionado por el administrador) -->
        <table>
            <tr>
                <th>ID</th>
                <th>Hora de Entrada</th>
                <th>Hora de Salida</th>
            </tr>
            <?php while ($fila = $resultado_fichajes->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php echo $fila['id']; ?>
                    </td>
                    <td>
                        <?php echo date('d-m-Y H:i:s', strtotime($fila['hora_entrada'])); ?>
                    </td>
                    <td>
                        <?php echo is_null($fila['hora_salida']) ? '---' : date('d-m-Y H:i:s', strtotime($fila['hora_salida'])); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <footer>
        <div class="legal-info">
            <p>© 2024 Cliwo. Click and Work. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>

</html>
<?php
$consulta_fichajes->close(); // Cierra la consulta de fichajes
$conexion->close(); // Cierra la conexión a la base de datos
?>