<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminUserRepository;

/**
 * Servicio AuthService
 * 
 * Responsabilidad única: Autenticación segura y gestión de sesiones para el panel administrativo.
 * Consulta la base de datos MySQL y verifica las contraseñas encriptadas mediante algoritmos estándar (Bcrypt).
 */
class AuthService
{
    private ?AdminUserRepository $userRepository;

    public function __construct(?AdminUserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Intenta autenticar un usuario consultando la base de datos y validando el hash bcrypt.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login(string $username, string $password): bool
    {
        $cleanUsername = trim($username);

        if ($cleanUsername === '' || $password === '') {
            return false;
        }

        if ($this->userRepository === null) {
            error_log("Aviso de seguridad: AdminUserRepository no está disponible.");
            return false;
        }

        // Buscar el usuario en la base de datos
        $user = $this->userRepository->findActiveByUsername($cleanUsername);

        if (!$user) {
            return false;
        }

        // Validar contraseña encriptada usando password_verify de PHP (Bcrypt)
        if (password_verify($password, $user['password_hash'])) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            // Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true);

            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_user_name'] = $user['full_name'] ?? $user['username'];
            $_SESSION['admin_user_id'] = (int)$user['id'];
            $_SESSION['admin_logged_in_at'] = time();

            // Actualizar fecha de último login
            try {
                $this->userRepository->updateLastLogin((int)$user['id']);

                // Si el algoritmo de hash necesita actualizarse a un costo superior
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $this->userRepository->updatePasswordHash((int)$user['id'], $newHash);
                }
            } catch (\Throwable $e) {
                // Registro silencioso de log para no interrumpir el inicio de sesión
                error_log("Error al actualizar metadata de login: " . $e->getMessage());
            }

            return true;
        }

        return false;
    }

    /**
     * Verifica si el usuario actual tiene una sesión válida activa.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['admin_user']);
    }

    /**
     * Retorna el nombre de usuario actualmente autenticado.
     *
     * @return string|null
     */
    public function getLoggedUsername(): ?string
    {
        return $_SESSION['admin_user'] ?? null;
    }

    /**
     * Retorna el nombre legible del organizador autenticado.
     *
     * @return string|null
     */
    public function getLoggedFullName(): ?string
    {
        return $_SESSION['admin_user_name'] ?? $_SESSION['admin_user'] ?? null;
    }

    /**
     * Cierra la sesión activa de forma segura.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get("session.use_cookies") && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();
        }
    }
}
