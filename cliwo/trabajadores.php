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

if (isset($_POST['nombre'], $_POST['apellidos'], $_POST['dni'], $_POST['Usuario'], $_POST['telefono'], $_POST['puesto'], $_POST['clave'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $dni = $_POST['dni'];
    $Usuario = $_POST['Usuario']; // Correo electrónico o usuario
    $telefono = $_POST['telefono'];
    $puesto = $_POST['puesto'];
    $clave = $_POST['clave'];
    $nombre_completo = $nombre . ' ' . $apellidos;

    // Verificar si el correo electrónico ya está registrado
    $consultaExistente = $conexion->prepare("SELECT id FROM usuarios WHERE Usuario = ?");
    $consultaExistente->bind_param("s", $Usuario);
    $consultaExistente->execute();
    $resultadoExistente = $consultaExistente->get_result();

    if ($resultadoExistente->num_rows > 0) {
        // Si encuentra un correo electrónico duplicado, establece un mensaje de error.
        $_SESSION['error'] = "El correo electrónico ya está registrado.";
    } else {
        // Intenta insertar el nuevo registro si el correo no está duplicado
        $consulta = $conexion->prepare("INSERT INTO usuarios (Usuario, Nombre_Completo, nombre, apellidos, dni, telefono, puesto, clave) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $consulta->bind_param("ssssssss", $Usuario, $nombre_completo, $nombre, $apellidos, $dni, $telefono, $puesto, $clave);

        if ($consulta->execute()) {
            $_SESSION['mensaje'] = "Usuario/trabajador añadido correctamente.";
        } else {
            $_SESSION['error'] = "Error al insertar el usuario/trabajador: " . $consulta->error;
        }
    }

    header("Location: trabajadores.php");
    exit;
}


$usuarios = $conexion->query("SELECT id, nombre, apellidos, dni, Usuario, telefono, puesto FROM usuarios");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Trabajadores</title>
    <!-- Enlace a la hoja de estilos para aplicar formato al contenido -->
    <link rel="stylesheet" href="styles.css">
    <link rel="preload" href="img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">

</head>

<body>
    <div class="container-grande">
        <div class="logo"><img src="img/logo.png" alt="Logo" /></div>
        <!-- Muestra el título de la página y el logo de la empresa -->
        <h1>Registro de Trabajadores</h1>

        <!-- Sección para mostrar mensajes de éxito o error -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje-exito">
                <?= $_SESSION['mensaje']; // Muestra el mensaje de éxito
                    unset($_SESSION['mensaje']); // Limpia el mensaje de la sesión         ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="mensaje-error">
                <?= $_SESSION['error']; // Muestra el mensaje de error
                    unset($_SESSION['error']); // Limpia el mensaje de la sesión         ?>
            </div>
        <?php endif; ?>

        <!-- Enlace para regresar a la página principal -->
        <div><a href="paginaprincipal.php" class="boton">Volver a la página principal</a></div>

        <!-- Asegúrate de que tu formulario esté dentro de un contenedor con clase para aplicar estilos -->

        <div class="container">
            <!-- Formulario para añadir un nuevo trabajador -->
            <form action="trabajadores.php" method="post">
                <!-- Campos para ingresar los datos del nuevo trabajador -->
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                </div>
                <div class="form-group">
                    <input type="text" name="apellidos" placeholder="Apellidos" required>
                </div>
                <div class="form-group">
                    <input type="text" name="dni" placeholder="DNI" required>
                </div>
                <div class="form-group">
                    <input type="email" name="Usuario" placeholder="Correo Electrónico" required>
                </div>
                <div class="form-group">
                    <input type="password" name="clave" placeholder="Clave" required>
                </div>
                <div class="form-group">
                    <input type="text" name="telefono" placeholder="Teléfono" required>
                </div>
                <div class="form-group">
                    <input type="text" name="puesto" placeholder="Puesto" required>
                </div>
                <!-- Botón para enviar el formulario y añadir el trabajador -->
                <button type="submit" class="boton">Añadir Trabajador</button>
            </form>
        </div>


        <!-- Tabla para mostrar los trabajadores existentes -->
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>DNI</th>
                <th>Usuario</th>
                <th>Teléfono</th>
                <th>Puesto</th>
                <th>Acciones</th>
            </tr>
            <?php while ($fila = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($fila['id']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['nombre']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['apellidos']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['dni']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['Usuario']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['telefono']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['puesto']); ?>
                    </td>
                    <td>
                        <!-- Enlace para eliminar el usuario, asegúrate de implementar esta funcionalidad de forma segura -->
                        <a class="boton" href="eliminarUsuario.php?id=<?= $fila['id']; ?>"
                            onclick="return confirm('¿Estás seguro?');">Eliminar</a>
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
// Cierra la conexión a la base de datos.
$conexion->close();
?>