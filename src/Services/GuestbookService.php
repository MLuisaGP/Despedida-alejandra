<?php

namespace App\Services;

use App\Models\GuestbookMessage;
use App\Repositories\GuestbookRepository;
use InvalidArgumentException;

/**
 * Servicio GuestbookService
 * 
 * Responsabilidad única: Reglas de negocio, saneamiento de datos (anti-XSS)
 * y validaciones para la firma del libro de recuerdos.
 */
class GuestbookService
{
    private GuestbookRepository $repository;

    public function __construct(GuestbookRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Valida y crea una nueva firma en el libro de recuerdos.
     *
     * @param array $data Datos de entrada (author, message)
     * @return GuestbookMessage
     * @throws InvalidArgumentException Si las validaciones fallan.
     */
    public function signBook(array $data): GuestbookMessage
    {
        $author = isset($data['author']) ? trim((string)$data['author']) : '';
        $message = isset($data['message']) ? trim((string)$data['message']) : '';

        // Validaciones de negocio
        if (empty($author)) {
            throw new InvalidArgumentException("Por favor ingresa tu nombre en 'De parte de'.");
        }

        if (mb_strlen($author) < 2) {
            throw new InvalidArgumentException("El nombre debe tener al menos 2 caracteres.");
        }

        if (mb_strlen($author) > 150) {
            throw new InvalidArgumentException("El nombre no puede superar los 150 caracteres.");
        }

        if (empty($message)) {
            throw new InvalidArgumentException("Por favor escribe un deseo, anécdota o recuerdo para los novios.");
        }

        if (mb_strlen($message) < 3) {
            throw new InvalidArgumentException("Tu mensaje debe tener al menos 3 caracteres.");
        }

        if (mb_strlen($message) > 2000) {
            throw new InvalidArgumentException("Tu mensaje es muy extenso (máximo 2000 caracteres).");
        }

        // Sanitización contra ataques Cross-Site Scripting (XSS)
        $cleanAuthor = htmlspecialchars(strip_tags($author), ENT_QUOTES, 'UTF-8');
        $cleanMessage = htmlspecialchars(strip_tags($message), ENT_QUOTES, 'UTF-8');

        $entry = new GuestbookMessage($cleanAuthor, $cleanMessage);

        return $this->repository->save($entry);
    }

    /**
     * Obtiene la lista de mensajes recientes para mostrar en la web.
     *
     * @param int $limit
     * @return GuestbookMessage[]
     */
    public function getRecentMessages(int $limit = 50): array
    {
        return $this->repository->getRecentApproved($limit);
    }

    /**
     * Total de firmas registradas.
     *
     * @return int
     */
    public function getTotalSignatures(): int
    {
        return $this->repository->countApproved();
    }

    /**
     * Obtiene una dedicatoria por su ID.
     *
     * @param int $id
     * @return GuestbookMessage|null
     */
    public function getMessageById(int $id): ?GuestbookMessage
    {
        return $this->repository->findById($id);
    }

    /**
     * Elimina una dedicatoria por su ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteMessage(int $id): bool
    {
        return $this->repository->deleteById($id);
    }
}
