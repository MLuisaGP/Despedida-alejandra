<?php

namespace App\Services;

use App\Models\RsvpResponse;
use App\Repositories\RsvpRepository;
use InvalidArgumentException;
use DomainException;

/**
 * Servicio RsvpService
 * 
 * Responsabilidad única: Reglas de negocio y validación de datos para la confirmación de asistencia,
 * incluyendo el control estricto del cupo máximo de personas (límite de 100 personas).
 */
class RsvpService
{
    public const DEFAULT_MAX_ATTENDEES = 0; // 0 = Sin límite de cupo

    private RsvpRepository $repository;
    private int $maxAttendees;

    public function __construct(RsvpRepository $repository, int $maxAttendees = 0)
    {
        $this->repository = $repository;
        $this->maxAttendees = max(0, $maxAttendees);
    }

    /**
     * Retorna el límite máximo configurado de asistentes.
     */
    public function getMaxAttendees(): int
    {
        return $this->maxAttendees;
    }

    /**
     * Total de personas actualmente confirmadas (titular + acompañantes).
     */
    public function getConfirmedCount(): int
    {
        return $this->repository->countConfirmedPeople();
    }

    /**
     * Retorna cuántos lugares quedan disponibles.
     */
    public function getAvailableSpots(): int
    {
        if ($this->maxAttendees <= 0) {
            return 999999;
        }
        return max(0, $this->maxAttendees - $this->getConfirmedCount());
    }

    /**
     * Verifica si se ha alcanzado el cupo máximo permitido.
     */
    public function isCapacityFull(): bool
    {
        if ($this->maxAttendees <= 0) {
            return false;
        }
        return $this->getConfirmedCount() >= $this->maxAttendees;
    }

    /**
     * Valida y registra una confirmación o declinación de asistencia.
     * Si el cupo de 100 personas se excede, rechaza la operación.
     *
     * @param array $data
     * @return RsvpResponse
     * @throws InvalidArgumentException En caso de datos inválidos.
     * @throws DomainException En caso de exceder el límite de cupo.
     */
    public function registerRsvp(array $data): RsvpResponse
    {
        $firstName = isset($data['first_name']) ? trim((string)$data['first_name']) : '';
        $lastName  = isset($data['last_name']) ? trim((string)$data['last_name']) : '';
        $status    = isset($data['attendance_status']) ? trim((string)$data['attendance_status']) : 'confirmed';
        $roleType  = isset($data['role_type']) ? trim((string)$data['role_type']) : 'invitado';
        $companions= isset($data['companions']) ? (int)$data['companions'] : 0;
        $phone     = isset($data['phone']) ? trim((string)$data['phone']) : null;
        $notes     = isset($data['notes']) ? trim((string)$data['notes']) : null;

        if (empty($firstName)) {
            throw new InvalidArgumentException("Por favor ingresa tu nombre.");
        }

        if (empty($lastName)) {
            throw new InvalidArgumentException("Por favor ingresa tus apellidos.");
        }

        if (!in_array($status, ['confirmed', 'declined'], true)) {
            $status = 'confirmed';
        }

        if (!in_array($roleType, ['invitado', 'organizador'], true)) {
            $roleType = 'invitado';
        }

        if ($companions < 0 || $companions > 10) {
            $companions = 0;
        }

        // CONTROL DE CUPO MÁXIMO DE ASISTENCIA (Opcional, desactivado para asistencia ilimitada)
        if ($this->maxAttendees > 0 && $status === 'confirmed') {
            $currentConfirmed = $this->getConfirmedCount();
            $incomingPeople = 1 + $companions;

            if ($currentConfirmed >= $this->maxAttendees) {
                throw new DomainException(
                    "Lo sentimos, se ha alcanzado el límite máximo de asistencia ({$this->maxAttendees} personas). El cupo está completo."
                );
            }

            if (($currentConfirmed + $incomingPeople) > $this->maxAttendees) {
                $available = max(0, $this->maxAttendees - $currentConfirmed);
                $lugarStr = $available === 1 ? '1 lugar disponible' : "{$available} lugares disponibles";
                throw new DomainException(
                    "Lo sentimos, solo queda {$lugarStr} para la despedida (intentaste registrar {$incomingPeople} personas)."
                );
            }
        }

        // Sanitización contra inyección de HTML/scripts
        $cleanFirstName = htmlspecialchars(strip_tags($firstName), ENT_QUOTES, 'UTF-8');
        $cleanLastName  = htmlspecialchars(strip_tags($lastName), ENT_QUOTES, 'UTF-8');
        $cleanPhone     = $phone ? htmlspecialchars(strip_tags($phone), ENT_QUOTES, 'UTF-8') : null;
        $cleanNotes     = $notes ? htmlspecialchars(strip_tags($notes), ENT_QUOTES, 'UTF-8') : null;

        $entry = new RsvpResponse(
            $cleanFirstName,
            $cleanLastName,
            $status,
            $roleType,
            $companions,
            $cleanPhone,
            $cleanNotes
        );

        return $this->repository->save($entry);
    }

    /**
     * Retorna la lista completa de todas las confirmaciones registradas.
     *
     * @return RsvpResponse[]
     */
    public function getAllAttendees(): array
    {
        return $this->repository->getAll();
    }
}
