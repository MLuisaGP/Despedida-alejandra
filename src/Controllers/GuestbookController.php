<?php

namespace App\Controllers;

use App\Services\GuestbookService;
use InvalidArgumentException;
use Exception;

/**
 * Controlador GuestbookController
 * 
 * Responsabilidad única: Atender peticiones HTTP para el libro de firmas y retornar respuestas JSON estructuradas.
 */
class GuestbookController
{
    private GuestbookService $service;

    public function __construct(GuestbookService $service)
    {
        $this->service = $service;
    }

    /**
     * Procesa la firma de un nuevo mensaje recibido por POST (JSON o FormData).
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

        // Obtener datos ya sea de $_POST o de php://input (JSON)
        $input = $_POST;
        if (empty($input)) {
            $rawJson = file_get_contents('php://input');
            $input = json_decode($rawJson, true) ?? [];
        }

        try {
            $savedMessage = $this->service->signBook($input);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => '¡Gracias por firmar nuestro libro de recuerdos! Tu deseo ha sido guardado con amor.',
                'data'    => $savedMessage
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
                'message' => 'Ocurrió un error inesperado al guardar tu mensaje. Por favor intenta de nuevo.'
            ]);
        }
    }

    /**
     * Retorna la lista de mensajes recientes en formato JSON.
     */
    public function listMessages(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $messages = $this->service->getRecentMessages(50);
            $total = $this->service->getTotalSignatures();

            echo json_encode([
                'success' => true,
                'total'   => $total,
                'data'    => $messages
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'No fue posible cargar las firmas en este momento.',
                'data'    => []
            ]);
        }
    }
}
