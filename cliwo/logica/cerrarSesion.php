<?php

session_start(); // Inicia una nueva sesión o reanuda la existente. Es necesario para poder acceder a las variables de sesión y luego poder limpiarlas.

session_unset(); // Limpia todas las variables de sesión. Esto elimina todos los datos registrados en las variables de sesión, pero la sesión en sí sigue activa.

session_destroy(); // Destruye toda la información asociada con la sesión actual. Esto termina efectivamente la sesión, eliminando la sesión del servidor.

header('Location: ../login.php'); // Redirecciona al usuario a la página de inicio de sesión. Esto se hace después de cerrar la sesión para
