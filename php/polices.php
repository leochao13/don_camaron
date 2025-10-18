<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener la ruta actual
$currentPath = $_SERVER['REQUEST_URI'];

// Si no hay sesión iniciada
if (!isset($_SESSION['usuario'])) {

    // Acceso a rutas de admin → 404
    if (str_starts_with($currentPath, '/components/admin/')) {
        http_response_code(404);
        header("Location: /404.php");
        exit();
    }

    // Acceso a rutas de usuario → login
    if (str_starts_with($currentPath, '/components/usuario/menu/')) {
        $_SESSION['error_mensaje'] = "Debes iniciar sesión para acceder al dashboard.";
        header("Location: /login/login.php");
        exit();
    }
}

// Si hay sesión, deja pasar todo (admin o usuario)
?>
