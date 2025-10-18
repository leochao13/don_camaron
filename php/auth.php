<?php
session_start();
require __DIR__ . "/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // -------------------------
    // LOGIN
    // -------------------------
    if (!empty($_POST['accion']) && $_POST['accion'] === "login") {
        $correo = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM public.usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['contrasena'])) {
            // Guardamos en sesión lo necesario
            $_SESSION['usuario'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['correo'] = $usuario['correo']; // 👈 agregado

            // Redirigimos según el rol
            if ($usuario['rol'] === 'admin') {
                header("Location: ../components/admin/index.php");
            } elseif ($usuario['rol'] === 'cliente') {
                header("Location: ../components/usuario/menu/index.php");
            } elseif ($usuario['rol'] === 'mesero') {
                header("Location: ../components/mesero/index.php");
            } else {
                // Si hay un rol desconocido
                header("Location: /403.php");
            }
            exit;
        } else {
            echo "❌ Correo o contraseña incorrectos.";
        }
    }

    // -------------------------
    // REGISTRO
    // -------------------------
    if (!empty($_POST['accion']) && $_POST['accion'] === "register") {
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol = "cliente"; // 👈 Por defecto, cliente

        try {
            $stmt = $pdo->prepare("
                INSERT INTO public.usuarios (nombre, correo, contrasena, rol)
                VALUES (:nombre, :correo, :contrasena, :rol)
            ");
            $stmt->execute([
                'nombre' => $nombre,
                'correo' => $correo,
                'contrasena' => $password,
                'rol' => $rol
            ]);
            echo "✅ Registro exitoso. Ahora puedes iniciar sesión.";
        } catch (PDOException $e) {
            echo "❌ Error al registrar: " . $e->getMessage();
        }
    }
}
