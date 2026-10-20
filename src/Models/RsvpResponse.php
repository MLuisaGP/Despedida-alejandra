<?php

namespace App\Models;

use JsonSerializable;

/**
 * Modelo RsvpResponse
 * 
 * Responsabilidad única: Representar los datos de una confirmación de asistencia.
 */
class RsvpResponse implements JsonSerializable
{
    private ?int $id;
    private string $firstName;
    private string $lastName;
    private string $attendanceStatus;
    private string $roleType;
    private int $companions;
    private ?string $phone;
    private ?string $notes;
    private ?string $createdAt;

    public function __construct(
        string $firstName,
        string $lastName,
        string $attendanceStatus = 'confirmed',
        string $roleType = 'invitado',
        int $companions = 0,
        ?string $phone = null,
        ?string $notes = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->attendanceStatus = in_array($attendanceStatus, ['confirmed', 'declined']) ? $attendanceStatus : 'confirmed';
        $this->roleType = in_array($roleType, ['invitado', 'organizador']) ? $roleType : 'invitado';
        $this->companions = max(0, $companions);
        $this->phone = $phone ? trim($phone) : null;
        $this->notes = $notes ? trim($notes) : null;
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getAttendanceStatus(): string
    {
        return $this->attendanceStatus;
    }

    public function isAttending(): bool
    {
        return $this->attendanceStatus === 'confirmed';
    }

    public function getRoleType(): string
    {
        return $this->roleType;
    }

    public function getCompanions(): int
    {
        return $this->companions;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                => $this->id,
            'full_name'         => $this->getFullName(),
            'first_name'        => $this->firstName,
            'last_name'         => $this->lastName,
            'attendance_status' => $this->attendanceStatus,
            'role_type'         => $this->roleType,
            'companions'        => $this->companions,
            'phone'             => $this->phone,
            'notes'             => $this->notes,
            'created_at'        => $this->createdAt
        ];
    }
}
