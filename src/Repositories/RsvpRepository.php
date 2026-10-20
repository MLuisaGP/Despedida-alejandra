<?php

namespace App\Repositories;

use App\Models\RsvpResponse;
use PDO;

/**
 * Repositorio RsvpRepository
 * 
 * Responsabilidad única: Ejecutar consultas SQL seguras (PDO Prepared Statements)
 * para el registro y consulta de asistentes al evento.
 */
class RsvpRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Guarda una nueva confirmación de asistencia.
     *
     * @param RsvpResponse $rsvp
     * @return RsvpResponse Retorna el objeto guardado con su ID generado.
     */
    public function save(RsvpResponse $rsvp): RsvpResponse
    {
        $sql = "INSERT INTO rsvp_attendees (first_name, last_name, attendance_status, role_type, companions, phone, notes, created_at)
                VALUES (:first_name, :last_name, :attendance_status, :role_type, :companions, :phone, :notes, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':first_name'        => $rsvp->getFirstName(),
            ':last_name'         => $rsvp->getLastName(),
            ':attendance_status' => $rsvp->getAttendanceStatus(),
            ':role_type'         => $rsvp->getRoleType(),
            ':companions'        => $rsvp->getCompanions(),
            ':phone'             => $rsvp->getPhone(),
            ':notes'             => $rsvp->getNotes()
        ]);

        $lastInsertId = (int)$this->db->lastInsertId();

        return new RsvpResponse(
            $rsvp->getFirstName(),
            $rsvp->getLastName(),
            $rsvp->getAttendanceStatus(),
            $rsvp->getRoleType(),
            $rsvp->getCompanions(),
            $rsvp->getPhone(),
            $rsvp->getNotes(),
            $lastInsertId,
            date('Y-m-d H:i:s')
        );
    }

    /**
     * Cuenta el total de personas confirmadas (titular + acompañantes).
     *
     * @return int
     */
    public function countConfirmedPeople(): int
    {
        $sql = "SELECT SUM(1 + companions) FROM rsvp_attendees WHERE attendance_status = 'confirmed'";
        $stmt = $this->db->query($sql);
        $count = $stmt->fetchColumn();
        return $count ? (int)$count : 0;
    }

    /**
     * Obtiene la lista completa de asistentes registrados.
     *
     * @return RsvpResponse[]
     */
    public function getAll(): array
    {
        $sql = "SELECT id, first_name, last_name, attendance_status, role_type, companions, phone, notes, created_at 
                FROM rsvp_attendees 
                ORDER BY created_at DESC";

        $stmt = $this->db->query($sql);
        $attendees = [];

        while ($row = $stmt->fetch()) {
            $attendees[] = new RsvpResponse(
                $row['first_name'],
                $row['last_name'],
                $row['attendance_status'],
                $row['role_type'],
                (int)$row['companions'],
                $row['phone'],
                $row['notes'],
                (int)$row['id'],
                $row['created_at']
            );
        }

        return $attendees;
    }
}
