<?php

namespace App\Controllers;

use App\Services\RsvpService;
use InvalidArgumentException;
use DomainException;
use Exception;

/**
 * Controlador RsvpController
 * 
 * Responsabilidad única: Atender peticiones HTTP para la confirmación de asistencia y retornar respuestas JSON.
 */
class RsvpController
{
    private RsvpService $service;

    public function __construct(RsvpService $service)
    {
        $this->service = $service;
    }

    /**
     * Procesa la confirmación o declinación enviada por POST.
     */
    public function submit(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido. Se requiere POST.'
            ]);
            return;
        }

        $input = $_POST;
        if (empty($input)) {
            $rawJson = file_get_contents('php://input');
            $input = json_decode($rawJson, true) ?? [];
        }

        try {
            $rsvp = $this->service->registerRsvp($input);

            $msg = $rsvp->isAttending()
                ? "¡Qué alegría saber que nos acompañarás, {$rsvp->getFirstName()}! Tu lugar está reservado."
                : "Muchas gracias por notificarnos, {$rsvp->getFirstName()}. Sentiremos mucho que no puedas asistir pero agradecemos tu cariño.";

            http_response_code(201);
            echo json_encode([
                'success'         => true,
                'message'         => $msg,
                'data'            => $rsvp,
                'confirmed_count' => $this->service->getConfirmedCount(),
                'max_attendees'   => $this->service->getMaxAttendees(),
                'available_spots' => $this->service->getAvailableSpots(),
                'is_full'         => $this->service->isCapacityFull()
            ]);
        } catch (DomainException $e) {
            http_response_code(422);
            echo json_encode([
                'success'         => false,
                'message'         => $e->getMessage(),
                'capacity_full'   => $this->service->isCapacityFull(),
                'available_spots' => $this->service->getAvailableSpots(),
                'max_attendees'   => $this->service->getMaxAttendees()
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ocurrió un error al procesar tu confirmación. Intenta de nuevo por favor.'
            ]);
        }
    }

    /**
     * Retorna métricas de asistencia.
     */
    public function stats(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $confirmedCount = $this->service->getConfirmedCount();

            echo json_encode([
                'success'         => true,
                'confirmed_count' => $confirmedCount,
                'max_attendees'   => $this->service->getMaxAttendees(),
                'available_spots' => $this->service->getAvailableSpots(),
                'is_full'         => $this->service->isCapacityFull()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estadísticas.'
            ]);
        }
    }

    /**
     * Retorna la lista completa de asistentes en formato JSON.
     */
    public function list(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $attendees = $this->service->getAllAttendees();
            $confirmedPeople = $this->service->getConfirmedCount();

            echo json_encode([
                'success'          => true,
                'total_responses'  => count($attendees),
                'confirmed_people' => $confirmedPeople,
                'max_attendees'    => $this->service->getMaxAttendees(),
                'available_spots'  => $this->service->getAvailableSpots(),
                'is_full'          => $this->service->isCapacityFull(),
                'data'             => $attendees
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener la lista de asistentes.'
            ]);
        }
    }
}
