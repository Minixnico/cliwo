<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="styles.css" />
</head>

<body>
  <div class="registro">
    <div class="logo">
      <img src="img/logo.png" />
    </div>
    <div class="login-container">
      <h2>Iniciar sesión</h2>
      <?php
      if (isset($_GET['error'])) {
        ?>
        <p class="error">
          <?php
          echo $_GET['error'];
          ?>
        </p>
        <?php
      }
      ?>
      <form action="logica/loguear.php" method="post">
        <input type="text" name="usuario" placeholder="Nombre de usuario" required />
        <input type="password" name="clave" placeholder="Contraseña" required />
        <button type="submit" class="boton">Iniciar sesión</button>
      </form>

    </div>
  </div>
  <footer>
    <div class="legal-info">
      <p>© 2024 Cliwo.Click and Work. Todos los derechos reservados.</p>
      <p>
        <a href="privacity.html">Política de privacidad</a> |
        <a href="conditions.html">Términos y condiciones</a>
      </p>
    </div>
  </footer>
</body>

</html>