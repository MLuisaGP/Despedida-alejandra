<?php

namespace App\Models;

use JsonSerializable;

/**
 * Modelo GuestbookMessage
 * 
 * Responsabilidad única: Representar la entidad de un mensaje en el libro de firmas.
 */
class GuestbookMessage implements JsonSerializable
{
    private ?int $id;
    private string $author;
    private string $message;
    private bool $isApproved;
    private ?string $createdAt;

    public function __construct(
        string $author,
        string $message,
        ?int $id = null,
        bool $isApproved = true,
        ?string $createdAt = null
    ) {
        $this->author = trim($author);
        $this->message = trim($message);
        $this->id = $id;
        $this->isApproved = $isApproved;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Retorna una fecha formateada amigable para humanos (ej. "19 de octubre, 2026").
     */
    public function getFormattedDate(): string
    {
        if (!$this->createdAt) {
            return 'Hace un momento';
        }

        $timestamp = strtotime($this->createdAt);
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $dia = date('j', $timestamp);
        $mes = $meses[(int)date('n', $timestamp)];
        $año = date('Y', $timestamp);

        return "{$dia} de {$mes}, {$año}";
    }

    /**
     * Serialización a JSON para respuestas de API
     */
    public function jsonSerialize(): array
    {
        return [
            'id'             => $this->id,
            'author'         => $this->author,
            'message'        => $this->message,
            'created_at'     => $this->createdAt,
            'formatted_date' => $this->getFormattedDate()
        ];
    }
}
