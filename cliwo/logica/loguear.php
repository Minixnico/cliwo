<?php

session_start();

// Verifica si se han enviado datos a través de POST
if (isset($_POST['usuario']) && isset($_POST['clave'])) {
    // Asigna los valores de $_POST a variables
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Establece la conexión a la base de datos
    $conexion = new mysqli("localhost", "root", "", "cliwodb");

    // Verifica si la conexión tiene errores
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Prepara la consulta SQL utilizando parámetros seguros para evitar inyección de SQL
    $consulta = $conexion->prepare("SELECT id, Nombre_Completo, Usuario FROM usuarios WHERE Usuario = ? AND Clave = ?");
    if (!$consulta) {
        die("Error al preparar la consulta: " . $conexion->error);
    }

    // Vincula los parámetros a la consulta preparada
    $consulta->bind_param("ss", $usuario, $clave);

    // Ejecuta la consulta
    if (!$consulta->execute()) {
        die("Error al ejecutar la consulta: " . $consulta->error);
    }

    // Obtiene el resultado de la consulta
    $resultado = $consulta->get_result();

    // Verifica si se encontró un usuario con las credenciales proporcionadas
    if ($resultado->num_rows == 1) {
        // Iniciar sesión y guardar el ID y nombre del usuario en la sesión
        $fila = $resultado->fetch_assoc();
        $_SESSION['usuario_id'] = $fila['id'];
        $_SESSION['nombre_usuario'] = $fila['Nombre_Completo'];

        // Verifica si el usuario es superadmin
        if ($fila['Usuario'] == 'superadmin') {
            // Redirige a la página principal para superadmin
            header("Location: ../paginaprincipal.php");
        } else {
            // Redirige a la página principal para trabajadores
            header("Location: ../paginatrabajador.php");
        }
        exit;
    } else {
        // Si las credenciales son incorrectas, redirigir de nuevo a login.php con un mensaje de error
        header("Location: ../login.php?error=Usuario o contraseña incorrectos");
        exit;
    }

} else {
    // Si no se enviaron datos a través de POST, muestra un mensaje de error
    echo "Error: No se recibieron datos de usuario y contraseña";
}

?>