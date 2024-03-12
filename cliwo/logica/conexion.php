<?php

$host = "localhost";
$user = "root";
$pass = "";

$db = "cliwodb";

$con = mysqli_connect($host, $user, $pass, $db);

if ($con) {
    echo "Conexión correcta";
} else {
    echo "Conexión fallida";
}