<?php
session_start();

// Redirigir si el usuario no está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Variables para mensajes
$mensaje = '';
$error = '';

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contrasenaActual = $_POST['contrasenaActual'];
    $nuevaContrasena = $_POST['nuevaContrasena'];
    $confirmacionContrasena = $_POST['confirmacionContrasena'];

    // Verificar que la nueva contraseña y confirmación coincidan
    if ($nuevaContrasena != $confirmacionContrasena) {
        $error = "Las nuevas contraseñas no coinciden.";
    } else {
        // Obtener la contraseña actual del usuario desde la base de datos
        $usuario_id = $_SESSION['usuario_id'];
        $consulta = $conexion->prepare("SELECT clave FROM usuarios WHERE id = ?");
        $consulta->bind_param("i", $usuario_id);
        $consulta->execute();
        $resultado = $consulta->get_result();
        if ($fila = $resultado->fetch_assoc()) {
            // Aquí cambiamos password_verify por una comparación directa
            if ($contrasenaActual == $fila['clave']) {
                // La contraseña actual es correcta, actualizar con la nueva contraseña
                // No se utiliza password_hash para la nueva contraseña
                $actualizarContrasena = $conexion->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
                $actualizarContrasena->bind_param("si", $nuevaContrasena, $usuario_id);
                if ($actualizarContrasena->execute()) {
                    $mensaje = "La contraseña ha sido cambiada exitosamente.";
                } else {
                    $error = "Error al cambiar la contraseña.";
                }
            } else {
                $error = "La contraseña actual es incorrecta.";
            }
        } else {
            $error = "Error al obtener la información del usuario.";
        }
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="img/logo.png" alt="Logo de CLIWO">
        </div>
        <div class="volver">
            <a href="paginaTrabajador.php" class="boton width">Volver a la página principal</a>
        </div>

        <h2>Cambiar Contraseña</h2>
        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-exito">
                <?= $mensaje; ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="mensaje-error">
                <?= $error; ?>
            </p>
        <?php endif; ?>

        <form action="cambiarContraseña.php" method="post">
            <div class="form-group">
                <label for="contrasenaActual">Contraseña Actual:</label>
                <input type="password" name="contrasenaActual" required>
            </div>
            <div class="form-group">
                <label for="nuevaContrasena">Nueva Contraseña:</label>
                <input type="password" name="nuevaContrasena" required>
            </div>
            <div class="form-group">
                <label for="confirmacionContrasena">Confirmar Nueva Contraseña:</label>
                <input type="password" name="confirmacionContrasena" required>
            </div>
            <div class="ancho">
                <button type="submit" class="boton width">Cambiar Contraseña</button>
            </div>
        </form>
    </div>
    <footer>
        <div class="legal-info">
            <p>© 2024 Cliwo. Click and Work. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>

</html>