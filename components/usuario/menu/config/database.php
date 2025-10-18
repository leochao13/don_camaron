<?php

class Database 
{
   private $hostname = "localhost";   // o la IP de tu servidor PostgreSQL
   private $port     = "5432";        // puerto por defecto de PostgreSQL
   private $database = "tienda_online"; 
   private $username = "postgres";    // usuario por defecto en pgAdmin
   private $password = "1234"; // la clave que pusiste al instalar PostgreSQL

   function conectar()
   {
      try {
         // DSN para PostgreSQL
         $conexion = "pgsql:host=" . $this->hostname . ";port=" . $this->port . ";dbname=" . $this->database;
         
         $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
         ];

         $pdo = new PDO($conexion, $this->username, $this->password, $options);

         return $pdo;
      } catch (PDOException $e) {
         echo 'Error conexión: ' . $e->getMessage();
         exit;
      }
   }
}
