<?php

// Asigna los detalles de conexión a variables
$host = "localhost"; // El host de la base de datos, comúnmente localhost para conexiones locales
$user = "root"; // El nombre de usuario para acceder a la base de datos
$pass = ""; // La contraseña para el usuario especificado

$db = "cliwodb"; // El nombre de la base de datos a la que se desea conectar

// Intenta establecer una conexión a la base de datos con los detalles proporcionados
$con = mysqli_connect($host, $user, $pass, $db);

// Verifica si la conexión fue exitosa
if ($con) {
    echo "Conexión correcta"; // Imprime este mensaje si la conexión fue exitosa
} else {
    echo "Conexión fallida"; // Imprime este mensaje si la conexión falló
}
