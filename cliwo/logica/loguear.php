<?php

session_start(); // Inicia una nueva sesión o reanuda la existente

// Verifica si se han enviado datos a través de POST
if (isset($_POST['usuario']) && isset($_POST['clave'])) {
    // Asigna los valores enviados por el formulario a variables
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Establece la conexión a la base de datos
    $conexion = new mysqli("localhost", "root", "", "cliwodb");

    // Verifica si la conexión tiene errores
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Prepara una consulta SQL segura para evitar inyección SQL
    $consulta = $conexion->prepare("SELECT id, Nombre_Completo, Usuario FROM usuarios WHERE Usuario = ? AND Clave = ?");
    if (!$consulta) {
        die("Error al preparar la consulta: " . $conexion->error);
    }

    // Vincula los parámetros de usuario y clave a la consulta preparada
    $consulta->bind_param("ss", $usuario, $clave);

    // Ejecuta la consulta
    if (!$consulta->execute()) {
        die("Error al ejecutar la consulta: " . $consulta->error);
    }

    // Obtiene el resultado de la consulta
    $resultado = $consulta->get_result();

    // Verifica si se encontró un usuario con las credenciales proporcionadas
    if ($resultado->num_rows == 1) {
        // Si se encuentra el usuario, inicia sesión y guarda su ID y nombre en variables de sesión
        $fila = $resultado->fetch_assoc();
        $_SESSION['usuario_id'] = $fila['id'];
        $_SESSION['nombre_usuario'] = $fila['Nombre_Completo'];

        // Verifica si el usuario es superadmin
        if ($fila['Usuario'] == 'superadmin') {
            // Redirige al superadmin a su página principal
            header("Location: ../paginaprincipal.php");
        } else {
            // Redirige a los usuarios normales a su página principal
            header("Location: ../paginatrabajador.php");
        }
        exit;
    } else {
        // Si las credenciales son incorrectas, redirige al usuario a la página de login con un mensaje de error
        header("Location: ../login.php?error=Usuario o contraseña incorrectos");
        exit;
    }
} else {
    // Si no se recibieron datos por POST, muestra un mensaje de error
    echo "Error: No se recibieron datos de usuario y contraseña";
}

?>