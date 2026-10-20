<?php

namespace App\Repositories;

use App\Models\GuestbookMessage;
use PDO;

/**
 * Repositorio GuestbookRepository
 * 
 * Responsabilidad única: Ejecutar consultas SQL seguras (PDO Prepared Statements)
 * para la persistencia y lectura de mensajes del libro de firmas.
 */
class GuestbookRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Guarda un nuevo mensaje en la base de datos.
     *
     * @param GuestbookMessage $message
     * @return GuestbookMessage Retorna el objeto actualizado con su ID y fecha generada.
     */
    public function save(GuestbookMessage $message): GuestbookMessage
    {
        $sql = "INSERT INTO guestbook_messages (author, message, is_approved, created_at) 
                VALUES (:author, :message, :is_approved, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':author'      => $message->getAuthor(),
            ':message'     => $message->getMessage(),
            ':is_approved' => $message->isApproved() ? 1 : 0
        ]);

        $lastInsertId = (int)$this->db->lastInsertId();

        // Obtener el registro recién insertado para tener su timestamp exacto
        return $this->findById($lastInsertId) ?? new GuestbookMessage(
            $message->getAuthor(),
            $message->getMessage(),
            $lastInsertId,
            $message->isApproved(),
            date('Y-m-d H:i:s')
        );
    }

    /**
     * Busca un mensaje por su ID.
     *
     * @param int $id
     * @return GuestbookMessage|null
     */
    public function findById(int $id): ?GuestbookMessage
    {
        $sql = "SELECT id, author, message, is_approved, created_at 
                FROM guestbook_messages 
                WHERE id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new GuestbookMessage(
            $row['author'],
            $row['message'],
            (int)$row['id'],
            (bool)$row['is_approved'],
            $row['created_at']
        );
    }

    /**
     * Obtiene los mensajes aprobados más recientes.
     *
     * @param int $limit Límite de mensajes a retornar.
     * @return GuestbookMessage[]
     */
    public function getRecentApproved(int $limit = 50): array
    {
        $sql = "SELECT id, author, message, is_approved, created_at 
                FROM guestbook_messages 
                WHERE is_approved = 1 
                ORDER BY created_at DESC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $messages = [];
        while ($row = $stmt->fetch()) {
            $messages[] = new GuestbookMessage(
                $row['author'],
                $row['message'],
                (int)$row['id'],
                (bool)$row['is_approved'],
                $row['created_at']
            );
        }

        return $messages;
    }

    /**
     * Cuenta el total de firmas registradas.
     *
     * @return int
     */
    public function countApproved(): int
    {
        $sql = "SELECT COUNT(*) FROM guestbook_messages WHERE is_approved = 1";
        $stmt = $this->db->query($sql);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Elimina un mensaje por su ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool
    {
        $sql = "DELETE FROM guestbook_messages WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
