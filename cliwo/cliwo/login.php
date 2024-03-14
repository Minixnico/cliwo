<!DOCTYPE html> <!-- Define el tipo de documento como HTML5 -->
<html lang="es"> <!-- Establece el idioma del contenido de la página en español -->

<head>
  <meta charset="UTF-8" /> <!-- Especifica la codificación de caracteres para la página -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Asegura una correcta visualización y un zoom adecuado en dispositivos móviles -->
  <title>Iniciar sesión</title> <!-- Define el título de la página, que aparece en la pestaña del navegador -->
  <link rel="stylesheet" href="styles.css" /> <!-- Vincula un archivo CSS externo para estilizar la página -->
  <link rel="preload" href="img/favicon.ico" type="image/x-icon">
  <link rel="icon" href="img/favicon.ico" type="image/x-icon">

</head>

<body>
  <div class="registro"> <!-- Contenedor principal para el formulario de registro -->
    <div class="logo"> <!-- Contenedor para el logo -->
      <img src="img/logo.png" /> <!-- Muestra el logo de la página -->
    </div>
    <div class="login-container"> <!-- Contenedor para el formulario de inicio de sesión -->
      <h2>Iniciar sesión</h2> <!-- Título del formulario -->

      <?php
      // Bloque PHP que verifica si la URL contiene el parámetro 'error'
      if (isset($_GET['error'])) {
        // Si existe, muestra el mensaje de error dentro de un párrafo con clase para estilos
        ?>
        <p class="error">
          <?php echo $_GET['error']; ?> <!-- Imprime el mensaje de error obtenido de la URL -->
        </p>
        <?php
      }
      ?>

      <form action="logica/loguear.php" method="post">
        <!-- Formulario para iniciar sesión, envía los datos a 'logica/loguear.php' mediante POST -->
        <input type="text" name="usuario" placeholder="Nombre de usuario" required />
        <!-- Campo para el nombre de usuario -->
        <input type="password" name="clave" placeholder="Contraseña" required /> <!-- Campo para la contraseña -->
        <button type="submit" class="boton">Iniciar sesión</button> <!-- Botón para enviar el formulario -->
      </form>
    </div>
  </div>
  <footer>
    <div class="legal-info"> <!-- Contenedor para la información legal y enlaces -->
      <p>© 2024 Cliwo.Click and Work. Todos los derechos reservados.</p> <!-- Información de derechos de autor -->
      <p>
        <a href="privacity.html">Política de privacidad</a> | <!-- Enlace a la política de privacidad -->
        <a href="conditions.html">Términos y condiciones</a> <!-- Enlace a los términos y condiciones -->
      </p>
    </div>
  </footer>
</body>

</html>