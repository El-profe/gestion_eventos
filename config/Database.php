<?php
// config/Database.php

class Database {
    private static string $host     = "localhost";
    private static string $db_name  = "gestion_eventos_db";
    private static string $username = "root";
    private static string $password = "";
    private static string $charset  = "utf8mb4";
    
    private static ?PDO $pdo = null;

    private function __construct() {}

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$pdo = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                error_log("Error de conexión PDO: " . $e->getMessage());
                die(json_encode([
                    'success' => false, 
                    'mensaje' => 'Error de conexión con el servidor de datos.'
                ]));
            }
        }
        return self::$pdo;
    }
}