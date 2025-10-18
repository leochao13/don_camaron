<?php
$host = "127.0.0.1";
$port = "5432";
$dbname = "tienda_online";
$user = "postgres";
$password = "1234";  // cámbiala por la que pusiste en la instalación

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("SET search_path TO public"); // usar el schema public
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
