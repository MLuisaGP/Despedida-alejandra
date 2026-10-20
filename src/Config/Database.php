<?php

namespace App\Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

/**
 * Clase Database
 * 
 * Responsabilidad única: Crear y gestionar la conexión PDO con la base de datos MySQL.
 * Implementa el patrón Singleton para reutilizar la conexión y evitar conexiones redundantes.
 */
class Database
{
    /** @var PDO|null Instancia compartida de la conexión PDO */
    private static ?PDO $connection = null;

    /**
     * Constructor privado para prevenir instanciación directa (Singleton)
     */
    private function __construct() {}

    /**
     * Obtiene la instancia única de conexión PDO a MySQL.
     * Carga variables de entorno si están disponibles en archivo .env.
     *
     * @return PDO
     * @throws PDOException En caso de error de conexión.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            // Intentar cargar variables de entorno desde .env si existe
            $envPath = dirname(__DIR__, 2);
            if (file_exists($envPath . '/.env') && class_exists(Dotenv::class)) {
                $dotenv = Dotenv::createImmutable($envPath);
                $dotenv->safeLoad();
            }

            // Obtener credenciales con valores por defecto seguros
            $host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
            $port     = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
            $dbName   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'despedida_db';
            $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'despedida_user';
            $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'despedida_pass_2026';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Registrar error o re-lanzar con mensaje claro
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                throw new PDOException("No fue posible conectar a la base de datos. Por favor verifica las credenciales en .env o el estado del contenedor Docker.");
            }
        }

        return self::$connection;
    }
}
