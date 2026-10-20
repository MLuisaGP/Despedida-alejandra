<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Repositories\GuestbookRepository;
use App\Repositories\RsvpRepository;
use App\Services\GuestbookService;
use App\Services\RsvpService;
use App\Controllers\GuestbookController;
use App\Controllers\RsvpController;

// Configuración de cabeceras de respuesta API
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    // Inicializar conexión a base de datos
    $db = Database::getConnection();

    // Inyección de dependencias para Guestbook
    $guestbookRepo = new GuestbookRepository($db);
    $guestbookService = new GuestbookService($guestbookRepo);
    $guestbookController = new GuestbookController($guestbookService);

    // Inyección de dependencias para RSVP con control de cupo
    $maxAttendees = (int)($_ENV['MAX_ATTENDEES'] ?? 100);
    $rsvpRepo = new RsvpRepository($db);
    $rsvpService = new RsvpService($rsvpRepo, $maxAttendees);
    $rsvpController = new RsvpController($rsvpService);

    // Determinar la acción solicitada por endpoint o query parameter
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_GET['endpoint'] ?? $_GET['action'] ?? '';
    $endpoint = trim(strtolower($endpoint), '/');

    // Enrutamiento simple y limpio
    switch ($endpoint) {
        // --- LIBRO DE FIRMAS ---
        case 'guestbook':
        case 'guestbook/messages':
            if ($method === 'POST') {
                $guestbookController->submit();
            } else {
                $guestbookController->listMessages();
            }
            break;

        case 'guestbook_submit':
            $guestbookController->submit();
            break;

        case 'guestbook_list':
            $guestbookController->listMessages();
            break;

        // --- CONFIRMACIÓN RSVP ---
        case 'rsvp':
            if ($method === 'POST') {
                $rsvpController->submit();
            } else {
                $rsvpController->stats();
            }
            break;

        case 'rsvp_submit':
            $rsvpController->submit();
            break;

        case 'rsvp_stats':
            $rsvpController->stats();
            break;

        case 'rsvp/list':
        case 'rsvp_list':
            $rsvpController->list();
            break;

        // --- RUTA NO ENCONTRADA ---
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint no encontrado.',
                'available_endpoints' => [
                    'POST /api/guestbook' => 'Firmar libro de visitas',
                    'GET /api/guestbook'  => 'Listar firmas',
                    'POST /api/rsvp'      => 'Confirmar asistencia',
                    'GET /api/rsvp'       => 'Estadísticas de confirmación'
                ]
            ]);
            break;
    }
} catch (\PDOException $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos MySQL.',
        'detail'  => $e->getMessage()
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno en el servidor.',
        'detail'  => $e->getMessage()
    ]);
}
