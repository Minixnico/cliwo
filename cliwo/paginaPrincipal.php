<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLIWO</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

    <div class="container">
        <div class="logo">
            <img src="img/logo.png" alt="Logo de CLIWO">
        </div>

        <div class="contenido">
            <?php
            session_start();
            if (isset($_SESSION['nombre_usuario'])) {
                $usuario = $_SESSION['nombre_usuario'];
                echo "<h1>BIENVENIDO a CLIWO, $usuario</h1>";
            } else {
                // Si el usuario no ha iniciado sesión, redirigirlo a la página de inicio de sesión
                // header("Location: login.php");
                exit;
            }
            ?>
        </div>


        <div class="menu">
            <a href="fichaje.php"> <img src="img/fichaje.png" alt="fichar" class="landing">Fichar</a>
            <a href="trabajadores.php"><img src="img/trabajadores.png" alt="trabajadores" class="landing"> Mis
                trabajadores</a>
            <a href="horarios.php"> <img src="img/horario.png" alt="horarios" class="landing">Horarios</a>
            <a href="gestionarAusencias.php"><img src="img/vacaciones.png" alt="Gestionar ausencias" class="landing">
                Gestión de Vacaciones y Ausencias</a>
            <a href="logica/cerrarSesion.php"><img src="img/cerrarSesion.png" alt="Cerrar sesión" class="landing">
                Cerrar sesión</a>

        </div>
    </div>

    <footer>
        <div class="legal-info">
            <p>© 2024 Cliwo.Click and Work. Todos los derechos reservados.</p>
        </div>
    </footer>

</body>

</html>