<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$conexion = new mysqli("localhost", "root", "", "cliwodb");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Añadir un nuevo trabajador y usuario
if (isset($_POST['nombre'], $_POST['apellidos'], $_POST['dni'], $_POST['correo_electronico'], $_POST['telefono'], $_POST['puesto'], $_POST['clave'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $dni = $_POST['dni'];
    $correo_electronico = $_POST['correo_electronico'];
    $telefono = $_POST['telefono'];
    $puesto = $_POST['puesto'];
    $clave = $_POST['clave']; // Almacenar la clave directamente

    // Comienza la transacción
    $conexion->begin_transaction();
    try {
        // Primero, inserta el nuevo usuario en la tabla usuarios
        $usuario = $correo_electronico; // Usando el correo electrónico como nombre de usuario
        $consultaUsuario = $conexion->prepare("INSERT INTO usuarios (Usuario, Clave, Nombre_Completo) VALUES (?, ?, ?)");
        $nombre_completo = $nombre . ' ' . $apellidos;
        $consultaUsuario->bind_param("sss", $usuario, $clave, $nombre_completo);
        $consultaUsuario->execute();
        $usuario_id = $conexion->insert_id;

        // Luego, inserta en la tabla trabajadores, incluyendo el usuario_id
        $consulta = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, correo_electronico, telefono, puesto, clave, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $consulta->bind_param("sssssssi", $nombre, $apellidos, $dni, $correo_electronico, $telefono, $puesto, $clave, $usuario_id);
        $consulta->execute();

        $conexion->commit();
        $_SESSION['mensaje'] = "Trabajador y usuario añadidos correctamente.";
    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['error'] = "Error al insertar el trabajador y usuario: " . $e->getMessage();
    }

    header("Location: trabajadores.php");
    exit;
}

// Eliminar trabajador si se proporciona un ID en la URL
if (isset($_GET['eliminar'])) {
    $id_trabajador = $_GET['eliminar'];

    // Primero, eliminamos el trabajador de la tabla 'trabajadores'
    $eliminacion_trabajador = $conexion->prepare("DELETE FROM trabajadores WHERE id = ?");
    $eliminacion_trabajador->bind_param("i", $id_trabajador);
    $eliminacion_trabajador->execute();

    // Luego, eliminamos el usuario correspondiente de la tabla 'usuarios'
    $eliminacion_usuario = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $eliminacion_usuario->bind_param("i", $id_trabajador); // Suponiendo que el ID del usuario es el mismo que el ID del trabajador
    $eliminacion_usuario->execute();

    $_SESSION['mensaje'] = "Trabajador eliminado correctamente.";
    header("Location: trabajadores.php");
    exit;
}


$trabajadores = $conexion->query("SELECT id, nombre, apellidos, dni, correo_electronico, telefono, puesto FROM trabajadores");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro de Trabajadores</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div>
        <div class="logo"><img src="img/logo.png" alt="Logo" /></div>
        <h1>Registro de Trabajadores</h1>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje-exito">
                <?= $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="mensaje-error">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div><a href="paginaprincipal.php" class="boton width">Volver a la página principal</a></div>

        <form action="trabajadores.php" method="post">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellidos" placeholder="Apellidos" required>
            <input type="text" name="dni" placeholder="DNI" required>
            <input type="email" name="correo_electronico" placeholder="Correo Electrónico" required>
            <input type="password" name="clave" placeholder="Clave" required>
            <input type="text" name="telefono" placeholder="Teléfono" required>
            <input type="text" name="puesto" placeholder="Puesto" required>
            <button type="submit" class="boton">Añadir Trabajador</button>
        </form>
        <!-- Tabla para mostrar los trabajadores existentes -->
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>DNI</th>
                <th>Correo Electrónico</th>
                <th>Teléfono</th>
                <th>Puesto</th>
                <th>Acciones</th>
            </tr>
            <?php while ($fila = $trabajadores->fetch_assoc()): ?>
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
                        <?= htmlspecialchars($fila['correo_electronico']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['telefono']); ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($fila['puesto']); ?>
                    </td>
                    <td>
                        <a class="boton" href="trabajadores.php?eliminar=<?= $fila['id']; ?>"
                            onclick="return confirm('¿Estás seguro de que deseas eliminar este trabajador?');">Eliminar</a>
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