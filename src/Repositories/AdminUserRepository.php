<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Repositorio AdminUserRepository
 * 
 * Responsabilidad única: Consultas y operaciones en la tabla admin_users de la base de datos.
 */
class AdminUserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Busca un usuario activo por su nombre de usuario.
     *
     * @param string $username
     * @return array|null Retorna los datos del usuario o null si no existe.
     */
    public function findActiveByUsername(string $username): ?array
    {
        $sql = "SELECT id, username, password_hash, full_name, is_active, created_at, last_login_at 
                FROM `admin_users` 
                WHERE LOWER(username) = LOWER(:username) 
                  AND is_active = 1 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Actualiza la fecha y hora de último acceso del usuario.
     *
     * @param int $userId
     * @return void
     */
    public function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE `admin_users` SET last_login_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    /**
     * Actualiza el hash de la contraseña (para cuando el algoritmo de PHP se actualice).
     *
     * @param int $userId
     * @param string $newHash
     * @return void
     */
    public function updatePasswordHash(int $userId, string $newHash): void
    {
        $sql = "UPDATE `admin_users` SET password_hash = :hash WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['hash' => $newHash, 'id' => $userId]);
    }
}
