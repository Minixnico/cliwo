<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Asegúrate de que el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Si el usuario es administrador, obtener lista de trabajadores
$listaTrabajadores = [];
if ($usuario_id == 1) {
    $resultado = $conexion->query("SELECT id, Nombre_Completo FROM usuarios ORDER BY Nombre_Completo");
    while ($fila = $resultado->fetch_assoc()) {
        $listaTrabajadores[$fila['id']] = $fila['Nombre_Completo'];
    }
}

// Determinar el ID del usuario para mostrar fichajes (admin puede seleccionar, otros ven los suyos)
$usuario_id_mostrar = $usuario_id;
if (isset($_POST['usuario_id']) && array_key_exists($_POST['usuario_id'], $listaTrabajadores)) {
    $usuario_id_mostrar = $_POST['usuario_id'];
}

// Verificar el último fichaje del usuario seleccionado
$consulta_ultimo_fichaje = $conexion->prepare("SELECT hora_salida FROM fichajes WHERE usuario_id = ? ORDER BY hora_entrada DESC LIMIT 1");
$consulta_ultimo_fichaje->bind_param("i", $usuario_id_mostrar);
$consulta_ultimo_fichaje->execute();
$resultado_ultimo_fichaje = $consulta_ultimo_fichaje->get_result();
$ultimo_fichaje = $resultado_ultimo_fichaje->fetch_assoc();

$mostrar_boton_salida = false;
if ($ultimo_fichaje && is_null($ultimo_fichaje['hora_salida'])) {
    $mostrar_boton_salida = true;
}

// Procesar acción de fichaje para el usuario actual, no cambia con selección de admin
if (isset($_POST['accion']) && $usuario_id == $_SESSION['usuario_id']) {
    $accion = $_POST['accion'];
    $consulta = null;

    if ($accion == 'entrada') {
        $consulta = $conexion->prepare("INSERT INTO fichajes (usuario_id, hora_entrada) VALUES (?, NOW())");
    } elseif ($accion == 'salida') {
        $consulta = $conexion->prepare("UPDATE fichajes SET hora_salida = NOW() WHERE usuario_id = ? AND hora_salida IS NULL ORDER BY hora_entrada DESC LIMIT 1");
    }

    if ($consulta && $consulta->bind_param("i", $usuario_id) && $consulta->execute()) {
        $_SESSION['mensaje'] = "Fichaje de $accion realizado correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al realizar el fichaje.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Obtener todos los fichajes del usuario seleccionado
$consulta_fichajes = $conexion->prepare("SELECT id, hora_entrada, hora_salida FROM fichajes WHERE usuario_id = ? ORDER BY hora_entrada DESC");
$consulta_fichajes->bind_param("i", $usuario_id_mostrar);
$consulta_fichajes->execute();
$resultado_fichajes = $consulta_fichajes->get_result();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Fichajes</title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body>
    <div class="logo">
        <img src="img/logo.png" />
    </div>
    <h1>Bienvenido,
        <?php echo $_SESSION['nombre_usuario']; ?>
    </h1>

    <!-- Mostrar mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="mensaje-exito">
            <p>
                <?php echo $_SESSION['mensaje']; ?>
            </p>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <!-- Formulario de fichaje -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <?php if ($mostrar_boton_salida): ?>
            <input type="hidden" name="accion" value="salida">
            <button type="submit" class="boton">Fichar Salida</button>
        <?php else: ?>
            <input type="hidden" name="accion" value="entrada">
            <button type="submit" class="boton">Fichar Entrada</button>
        <?php endif; ?>
    </form>




    <!-- Selector de trabajador para administrador -->
    <?php if ($usuario_id == 1): ?>

        <a href="paginaPrincipal.php" class="boton width">Volver a la página principal</a>
        <h2>Registros de Fichajes</h2>
        <p class="texto">Seleccione un trabajador para ver su registro de fichajes</p>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <select name="usuario_id" <?php foreach ($listaTrabajadores as $id => $nombre): ?> <option
                    value="<?php echo $id; ?>" <?php echo $id == $usuario_id_mostrar ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="boton">Ver Fichajes</button>
        </form>
        <?php else: ?>
    <!-- Este bloque se ejecuta si el usuario no es el administrador -->

    <a href="paginatrabajador.php" class="boton width">Volver a la página principal</a>
    <h2>Registros de Fichajes</h2>
    <?php endif; ?>



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
</body>

</html>
<?php
$consulta_fichajes->close();
$conexion->close();
?>