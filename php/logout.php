<?php
session_start(); // Iniciar la sesión

// Destruir todas las variables de sesión
session_unset();

// Destruir la sesión actual
session_destroy();

// Redirigir al login
header("Location: /index.html");
exit();
?>