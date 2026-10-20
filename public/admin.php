<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Repositories\GuestbookRepository;
use App\Repositories\RsvpRepository;
use App\Repositories\AdminUserRepository;
use App\Services\GuestbookService;
use App\Services\RsvpService;
use App\Services\AuthService;
use Dotenv\Dotenv;

// Cargar variables de entorno
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env') && class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable($envPath);
    $dotenv->safeLoad();
}

// Inicializar conexión y servicio de autenticación con base de datos
try {
    $db = Database::getConnection();
    $adminUserRepo = new AdminUserRepository($db);
    $authService = new AuthService($adminUserRepo);
} catch (\Throwable $e) {
    error_log("Error conectando a BD en AuthService: " . $e->getMessage());
    $authService = new AuthService();
}

$errorMessage = null;

// --- ACCIONES DE AUTENTICACIÓN ---

// 1. Cerrar Sesión
if ((isset($_GET['action']) && $_GET['action'] === 'logout') || strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/logout') !== false) {
    $authService->logout();
    header('Location: /admin');
    exit;
}

// 2. Procesar Login (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($authService->login($username, $password)) {
        header('Location: /admin');
        exit;
    } else {
        $errorMessage = 'Nombre de usuario o contraseña incorrectos. Por favor intenta de nuevo.';
    }
}

// 4. Eliminar Dedicatoria del Libro de Recuerdos
if ($authService->isAuthenticated()) {
    $rawInput = [];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $rawInput = $_POST;
        if (empty($rawInput)) {
            $json = file_get_contents('php://input');
            $rawInput = json_decode($json, true) ?? [];
        }
    }

    if (isset($rawInput['action']) && $rawInput['action'] === 'delete_guestbook') {
        $deleteId = (int)($rawInput['id'] ?? 0);
        $deleted = false;
        if ($deleteId > 0) {
            try {
                $db = Database::getConnection();
                $guestbookRepo = new GuestbookRepository($db);
                $deleted = $guestbookRepo->deleteById($deleteId);
            } catch (\Throwable $e) {
                $deleted = false;
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Dedicatoria eliminada correctamente.' : 'No se pudo eliminar la dedicatoria.'
            ]);
            exit;
        }

        header('Location: /admin?tab=tab-libro&msg=deleted');
        exit;
    }
}

// 3. Exportar Lista de Asistentes a CSV (Excel)
if ($authService->isAuthenticated() && ((isset($_GET['action']) && $_GET['action'] === 'export_rsvp') || strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/export') !== false)) {
    try {
        $db = Database::getConnection();
        $rsvpRepo = new RsvpRepository($db);
        $attendees = $rsvpRepo->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="asistentes_despedida_alejandra_jesus_' . date('Y-m-d') . '.csv"');

        // BOM para compatibilidad con caracteres especiales en Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Nombre', 'Apellidos', 'Nombre Completo', 'Asistencia', 'Teléfono', 'Fecha de Registro']);

        foreach ($attendees as $a) {
            fputcsv($output, [
                $a->getId(),
                $a->getFirstName(),
                $a->getLastName(),
                $a->getFullName(),
                $a->isAttending() ? 'Confirmado' : 'Declinado',
                $a->getPhone() ?: 'N/A',
                $a->getCreatedAt()
            ]);
        }

        fclose($output);
        exit;
    } catch (\Throwable $e) {
        die("Error al generar CSV: " . $e->getMessage());
    }
}

// Si el usuario no está autenticado, renderizar la pantalla de login
if (!$authService->isAuthenticated()):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo | Despedida de Alejandra & Jesús</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Great+Vibes&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Favicon e Íconos Oficiales (Monograma A&J) -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
</head>
<body class="admin-login">

    <div class="admin-login__card">
        <img src="/assets/images/logo-aj.png" alt="A & J" style="height: 64px; width: 64px; border-radius: 50%; margin: 0 auto 12px; display: block; border: 2px solid #C5A059; object-fit: cover;">
        <div class="admin-login__logo">
            Alejandra <span>&</span> Jesús
        </div>
        <h1 class="admin-login__title">Panel de Control</h1>
        <p class="admin-login__subtitle">Ingresa tus credenciales para ver la lista de asistentes y el libro de recuerdos.</p>

        <?php if ($errorMessage): ?>
            <div class="admin-login__alert" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin" class="form">
            <input type="hidden" name="action" value="login">

            <div class="form__group">
                <label for="username" class="form__label">Nombre de Usuario <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form__control" placeholder="Ej. luisagalazmp" required autofocus>
            </div>

            <div class="form__group">
                <label for="password" class="form__label">Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form__control" placeholder="Tu contraseña de acceso" required>
            </div>

            <button type="submit" class="btn btn--gold btn--full" style="margin-top: 10px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Iniciar Sesión
            </button>
        </form>

        <div style="margin-top: 26px; border-top: 1px solid rgba(197, 160, 89, 0.2); padding-top: 18px;">
            <a href="/" style="font-size: 0.85rem; color: #6B635E; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Regresar a la Invitación
            </a>
        </div>
    </div>

</body>
</html>
<?php
exit;
endif;

// --- USUARIO AUTENTICADO: CARGAR DATOS DE HOSTING ---
$loggedUser = $authService->getLoggedFullName() ?? $authService->getLoggedUsername();

try {
    $db = Database::getConnection();

    // Servicios con control de cupo
    $maxAttendees = (int)($_ENV['MAX_ATTENDEES'] ?? 0); // 0 = Sin límite de cupo
    $rsvpRepo = new RsvpRepository($db);
    $rsvpService = new RsvpService($rsvpRepo, $maxAttendees);
    $attendees = $rsvpService->getAllAttendees();
    $totalConfirmedPeople = $rsvpService->getConfirmedCount();

    $guestbookRepo = new GuestbookRepository($db);
    $guestbookService = new GuestbookService($guestbookRepo);
    $guestbookMessages = $guestbookService->getRecentMessages(100);
    $totalSignatures = $guestbookService->getTotalSignatures();

    // Métricas
    $totalResponses = count($attendees);

    // --- VISTA NUEVA / INDIVIDUAL DE UNA DEDICATORIA ---
    $recuerdoId = null;
    if (isset($_GET['recuerdo_id'])) {
        $recuerdoId = (int)$_GET['recuerdo_id'];
    } elseif (preg_match('#/admin/recuerdo/(\d+)#', $_SERVER['REQUEST_URI'] ?? '', $matches)) {
        $recuerdoId = (int)$matches[1];
    }

    if ($recuerdoId !== null) {
        $singleMemory = $guestbookRepo->findById($recuerdoId);
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $singleMemory ? 'Dedicatoria de ' . htmlspecialchars($singleMemory->getAuthor()) : 'Dedicatoria no encontrada' ?> | Alejandra & Jesús</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Great+Vibes&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
</head>
<body class="admin-dashboard">

    <!-- BARRA SUPERIOR -->
    <header class="admin-dashboard__nav">
        <div class="container admin-dashboard__nav-inner">
            <div class="admin-dashboard__nav-logo" style="display: flex; align-items: center; gap: 10px;">
                <img src="/assets/images/logo-aj.png" alt="A & J" style="height: 40px; width: 40px; border-radius: 50%; border: 1.5px solid #C5A059; object-fit: cover;">
                <div>
                    Alejandra <span>&</span> Jesús
                    <small>Panel Administrativo</small>
                </div>
            </div>

            <div class="admin-dashboard__nav-actions">
                <a href="/admin?tab=tab-libro" class="btn btn--outline" style="padding: 8px 18px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Volver a la Lista de Recuerdos
                </a>
                <a href="/admin/logout" class="btn btn--gold" style="padding: 8px 18px; font-size: 0.8rem;">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <main class="container container--narrow" style="padding-top: 36px; padding-bottom: 60px;">
        <div style="margin-bottom: 20px;">
            <a href="/admin?tab=tab-libro" style="font-size: 0.88rem; color: #987532; display: inline-flex; align-items: center; gap: 6px; font-weight: 500;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Panel de Control / Libro de Recuerdos / Dedicatoria #<?= $recuerdoId ?>
            </a>
        </div>

        <?php if (!$singleMemory): ?>
            <div class="card" style="text-align: center; padding: 50px 20px; border: 1.5px dashed #C5A059; background: #FFFFFF; border-radius: 24px;">
                <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; color: #8E4A49; margin-bottom: 10px;">Dedicatoria no encontrada</h3>
                <p style="color: #6B635E; margin-bottom: 24px;">El mensaje que intentas consultar no existe o ha sido eliminado.</p>
                <a href="/admin?tab=tab-libro" class="btn btn--gold">Volver a la Lista de Recuerdos</a>
            </div>
        <?php else: ?>
            <div class="admin-single-view__card" style="background: #FFFFFF; border-radius: 28px; border: 1.5px solid #C5A059; padding: 40px 45px; box-shadow: 0 20px 45px rgba(60, 48, 38, 0.08); position: relative;">
                
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(197, 160, 89, 0.25); padding-bottom: 24px; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); border: 1.5px solid #C5A059; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="#C5A059">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </div>
                        <div>
                            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #987532;">Dedicatoria en el Libro</span>
                            <h1 style="font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; color: #2D2724; margin: 0; line-height: 1.2;">
                                <?= htmlspecialchars($singleMemory->getAuthor()) ?>
                            </h1>
                        </div>
                    </div>

                    <div style="font-size: 0.85rem; color: #6B635E; display: flex; align-items: center; gap: 6px; background: #FAF7F2; padding: 8px 16px; border-radius: 20px; border: 1px solid rgba(197, 160, 89, 0.2);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?= htmlspecialchars($singleMemory->getFormattedDate()) ?>
                        <span style="color: #9B938D;">(<?= date('H:i', strtotime($singleMemory->getCreatedAt() ?? 'now')) ?> hrs)</span>
                    </div>
                </div>

                <!-- Mensaje Completo en Tipografía Destacada -->
                <div style="position: relative; padding: 36px 40px; background: #FAF7F2; border-radius: 20px; border: 1px solid rgba(197, 160, 89, 0.25); margin-bottom: 30px;">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="rgba(197, 160, 89, 0.2)" style="position: absolute; top: 16px; left: 18px;">
                        <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/>
                    </svg>
                    <div id="memory-full-text" style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; line-height: 1.85; color: #2D2724; font-style: italic; position: relative; z-index: 1; white-space: pre-wrap; word-break: break-word;">
<?= htmlspecialchars($singleMemory->getMessage()) ?>
                    </div>
                </div>

                <!-- Barra de Acciones -->
                <div class="admin-single-view__actions" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding-top: 20px; border-top: 1px solid rgba(197, 160, 89, 0.2);">
                    <a href="/admin?tab=tab-libro" class="btn btn--outline" style="padding: 10px 20px; font-size: 0.85rem;">
                        ← Volver a la Lista
                    </a>

                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="copyFullMemory()" id="btn-copy-memory" class="btn btn--gold" style="padding: 10px 22px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copiar Dedicatoria
                        </button>

                        <button type="button" onclick="window.print()" class="btn btn--outline" style="padding: 10px 20px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Imprimir
                        </button>

                        <form method="POST" action="/admin" onsubmit="return confirm('¿Confirmas que deseas eliminar esta dedicatoria permanentemente? Esta acción no se puede deshacer.');" style="display: inline; margin: 0;">
                            <input type="hidden" name="action" value="delete_guestbook">
                            <input type="hidden" name="id" value="<?= $singleMemory->getId() ?>">
                            <button type="submit" class="btn" style="background: rgba(194, 123, 127, 0.12); color: #C27B7F; border: 1px solid rgba(194, 123, 127, 0.35); padding: 10px 18px; font-size: 0.85rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                Eliminar Dedicatoria
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function copyFullMemory() {
            const textElem = document.getElementById('memory-full-text');
            if (!textElem) return;
            const text = textElem.innerText.trim();
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('btn-copy-memory');
                const orig = btn.innerHTML;
                btn.innerHTML = '<span>¡Copiado con Éxito!</span>';
                setTimeout(() => { btn.innerHTML = orig; }, 2500);
            }).catch(err => {
                alert('No se pudo copiar el texto automáticamente.');
            });
        }
    </script>
</body>
</html>
<?php
        exit;
    }

} catch (\Throwable $e) {
    die("Error al consultar la base de datos de HostGator: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | Alejandra & Jesús</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Great+Vibes&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Estilos garantizados para el Modal de Lectura de Dedicatorias */
        .modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(30, 25, 22, 0.72) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            z-index: 99999 !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            box-sizing: border-box !important;
            overflow-y: auto !important;
        }
        .modal-card {
            background: #FFFFFF !important;
            border-radius: 24px !important;
            border: 1.5px solid #C5A059 !important;
            width: 100% !important;
            max-width: 680px !important;
            max-height: 90vh !important;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(197, 160, 89, 0.25) !important;
            overflow: hidden !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            margin: auto !important;
            animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(14px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        .modal-card button:disabled {
            opacity: 0.4 !important;
            cursor: not-allowed !important;
        }
        .guestbook-row {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .guestbook-row:hover {
            background: rgba(197, 160, 89, 0.08) !important;
        }
        @media (max-width: 640px) {
            .modal-backdrop {
                padding: 12px !important;
            }
            .modal-card {
                border-radius: 18px !important;
                max-height: 94vh !important;
            }
        }
    </style>
    <!-- Favicon e Íconos Oficiales (Monograma A&J) -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
</head>
<body class="admin-dashboard">

    <!-- BARRA SUPERIOR -->
    <header class="admin-dashboard__nav">
        <div class="container admin-dashboard__nav-inner">
            <div class="admin-dashboard__nav-logo" style="display: flex; align-items: center; gap: 10px;">
                <img src="/assets/images/logo-aj.png" alt="A & J" style="height: 40px; width: 40px; border-radius: 50%; border: 1.5px solid #C5A059; object-fit: cover;">
                <div>
                    Alejandra <span>&</span> Jesús
                    <small>Panel Administrativo</small>
                </div>
            </div>

            <div class="admin-dashboard__nav-actions">
                <span class="admin-dashboard__nav-user">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Hola, <strong><?= htmlspecialchars($loggedUser ?? 'Admin') ?></strong>
                </span>

                <a href="/" target="_blank" class="btn btn--outline" style="padding: 8px 18px; font-size: 0.8rem;">
                    Ver Invitación
                </a>

                <a href="/admin/logout" class="btn btn--gold" style="padding: 8px 18px; font-size: 0.8rem;">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <!-- MÉTRICAS PRINCIPALES -->
        <section class="admin-dashboard__metrics">
            <!-- Métrica 1: Personas Confirmadas -->
            <div class="admin-dashboard__metric-card">
                <div class="admin-dashboard__metric-card-icon admin-dashboard__metric-card-icon--teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="admin-dashboard__metric-card-info">
                    <div class="admin-dashboard__metric-card-number"><?= $totalConfirmedPeople ?></div>
                    <div class="admin-dashboard__metric-card-label">Personas Confirmadas</div>
                </div>
            </div>

            <!-- Métrica 2: Respuestas Totales -->
            <div class="admin-dashboard__metric-card">
                <div class="admin-dashboard__metric-card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="admin-dashboard__metric-card-info">
                    <div class="admin-dashboard__metric-card-number"><?= $totalResponses ?></div>
                    <div class="admin-dashboard__metric-card-label">Respuestas RSVP</div>
                </div>
            </div>

            <!-- Métrica 3: No Asistirán -->
            <div class="admin-dashboard__metric-card">
                <div class="admin-dashboard__metric-card-icon" style="background: rgba(194, 123, 127, 0.14); color: #C27B7F;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="admin-dashboard__metric-card-info">
                    <div class="admin-dashboard__metric-card-number"><?= count(array_filter($attendees, fn($a) => !$a->isAttending())) ?></div>
                    <div class="admin-dashboard__metric-card-label">No Asistirán</div>
                </div>
            </div>

            <!-- Métrica 4: Firmas en Libro -->
            <div class="admin-dashboard__metric-card">
                <div class="admin-dashboard__metric-card-icon admin-dashboard__metric-card-icon--rose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                    </svg>
                </div>
                <div class="admin-dashboard__metric-card-info">
                    <div class="admin-dashboard__metric-card-number"><?= $totalSignatures ?></div>
                    <div class="admin-dashboard__metric-card-label">Firmas en el Libro</div>
                </div>
            </div>
        </section>

        <!-- PESTAÑAS DE NAVEGACIÓN -->
        <div class="admin-dashboard__tabs">
            <button type="button" class="admin-dashboard__tab-btn active" data-tab="tab-asistentes">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                Lista de Asistentes (<?= count($attendees) ?>)
            </button>

            <button type="button" class="admin-dashboard__tab-btn" data-tab="tab-libro">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                Libro de Recuerdos & Deseos (<?= count($guestbookMessages) ?>)
            </button>
        </div>

        <!-- ==========================================
             PESTAÑA 1: LISTA DE ASISTENTES (RSVP)
             ========================================== -->
        <div id="tab-asistentes" class="admin-dashboard__tab-content active">
            <div class="admin-dashboard__toolbar">
                <div class="admin-dashboard__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="search-rsvp" placeholder="Buscar por nombre o teléfono...">
                </div>

                <a href="/admin/export" class="btn btn--outline" style="padding: 10px 22px; font-size: 0.85rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Descargar Excel (CSV)
                </a>
            </div>

            <div class="admin-dashboard__table-wrapper">
                <table class="admin-dashboard__table" id="table-rsvp">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo</th>
                            <th>Estado</th>
                            <th>Teléfono / WhatsApp</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendees)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #6B635E;">
                                    Aún no hay confirmaciones registradas en la base de datos.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendees as $index => $a): ?>
                                <tr>
                                    <td style="color: #9B938D;"><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($a->getFullName()) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($a->isAttending()): ?>
                                            <span class="admin-dashboard__badge admin-dashboard__badge--confirmed">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                                Confirmado
                                            </span>
                                        <?php else: ?>
                                            <span class="admin-dashboard__badge admin-dashboard__badge--declined">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                                                Declinado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a->getPhone()): ?>
                                            <?php $cleanPhone = preg_replace('/[^0-9]/', '', $a->getPhone()); ?>
                                            <a href="https://wa.me/<?= htmlspecialchars($cleanPhone) ?>" target="_blank" rel="noopener noreferrer" style="color: #2A9D8F; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
                                                <?= htmlspecialchars($a->getPhone()) ?>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.541 1.961.826 2.796.826 3.183 0 5.77-2.587 5.77-5.768 0-3.181-2.587-5.769-5.77-5.769zm3.364 8.205c-.148.417-.864.814-1.204.856-.34.043-.76.06-2.228-.549-1.854-.77-3.036-2.656-3.129-2.779-.092-.123-.748-.996-.748-1.9 0-.904.474-1.348.643-1.533.169-.185.37-.231.493-.231.123 0 .247.001.354.006.113.005.263-.043.411.314.154.37.525 1.28.571 1.373.046.092.077.2.015.323-.062.124-.092.201-.185.309-.092.108-.195.241-.278.324-.093.093-.19.195-.082.38.108.185.481.794 1.032 1.284.71.633 1.309.829 1.494.922.185.093.293.077.401-.046.108-.124.463-.54.586-.725.123-.186.247-.154.417-.093.17.062 1.079.509 1.264.602.185.092.308.139.354.216.047.078.047.448-.101.865z"/>
                                                </svg>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #9B938D;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.8rem; color: #6B635E;">
                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($a->getCreatedAt() ?? 'now'))) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==========================================
             PESTAÑA 2: LIBRO DE RECUERDOS (GUESTBOOK)
             ========================================== -->
        <div id="tab-libro" class="admin-dashboard__tab-content">
            <div class="admin-dashboard__toolbar">
                <div class="admin-dashboard__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="search-guestbook" placeholder="Buscar por autor o contenido del deseo...">
                </div>
            </div>

            <div class="admin-dashboard__table-wrapper">
                <table class="admin-dashboard__table" id="table-guestbook">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th style="width: 200px;">De parte de</th>
                            <th>Deseo, Anécdota o Recuerdo</th>
                            <th style="width: 150px;">Fecha</th>
                            <th style="width: 140px; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($guestbookMessages)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #6B635E;">
                                    Aún no hay dedicatorias firmadas en el libro.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($guestbookMessages as $index => $msg): ?>
                                <?php 
                                    $rawText = $msg->getMessage();
                                    $isLong = mb_strlen($rawText) > 110;
                                    $previewText = $isLong ? mb_substr($rawText, 0, 110) . '...' : $rawText;
                                ?>
                                <tr class="guestbook-row" data-index="<?= $index ?>" style="cursor: pointer;" title="Haz clic para ver el mensaje completo">
                                    <td style="color: #9B938D;"><?= $index + 1 ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#C5A059"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                            <strong><?= htmlspecialchars($msg->getAuthor()) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <p style="font-style: italic; color: #4A443F; line-height: 1.5; margin: 0;">
                                            "<?= nl2br(htmlspecialchars($previewText)) ?>"
                                            <?php if ($isLong): ?>
                                                <span style="color: #987532; font-weight: 600; font-style: normal; font-size: 0.82rem; margin-left: 4px;">[Leer completo →]</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td style="font-size: 0.8rem; color: #6B635E;">
                                        <?= htmlspecialchars($msg->getFormattedDate()) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <button type="button" class="btn btn--outline btn-open-memory" data-index="<?= $index ?>" style="padding: 6px 12px; font-size: 0.78rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;" title="Ver mensaje completo">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                Ver
                                            </button>
                                            <button type="button" class="btn-delete-memory" data-id="<?= $msg->getId() ?>" data-author="<?= htmlspecialchars($msg->getAuthor()) ?>" data-index="<?= $index ?>" style="background: rgba(194, 123, 127, 0.12); color: #C27B7F; border: 1px solid rgba(194, 123, 127, 0.35); padding: 6px 10px; border-radius: 9999px; font-size: 0.78rem; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;" title="Eliminar dedicatoria">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ==========================================
         MODAL / VISTA DE LECTURA DE RECUERDO
         ========================================== -->
    <div id="memory-modal" class="modal-backdrop" style="display: none;">
        <div class="modal-card">
            <!-- Modal Header -->
            <div style="background: linear-gradient(135deg, #FAF7F2 0%, #F5EFEB 100%); padding: 22px 28px; border-bottom: 1px solid rgba(197, 160, 89, 0.25); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(197, 160, 89, 0.35); flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#C5A059">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #987532;">Libro de Recuerdos</span>
                        <h3 id="modal-memory-author" style="font-family: 'Cormorant Garamond', serif; font-size: 1.55rem; color: #2D2724; margin: 0; line-height: 1.2;">
                            Autor del Mensaje
                        </h3>
                    </div>
                </div>

                <button type="button" id="modal-memory-close-btn" style="background: transparent; border: none; font-size: 1.8rem; color: #6B635E; cursor: pointer; line-height: 1; padding: 4px 8px; border-radius: 8px;" title="Cerrar vista">&times;</button>
            </div>

            <!-- Modal Content / Body -->
            <div style="padding: 26px 30px; max-height: 55vh; overflow-y: auto;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px dashed rgba(197, 160, 89, 0.25);">
                    <div style="font-size: 0.82rem; color: #6B635E; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span id="modal-memory-date">Fecha de registro</span>
                    </div>

                    <span id="modal-memory-counter" style="font-size: 0.75rem; background: rgba(197, 160, 89, 0.12); color: #987532; padding: 3px 10px; border-radius: 20px; font-weight: 600;">
                        1 de 10
                    </span>
                </div>

                <!-- Message text in stylized letter container -->
                <div style="position: relative; padding: 22px 24px; background: #FAF7F2; border-radius: 16px; border: 1px solid rgba(197, 160, 89, 0.2);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="rgba(197, 160, 89, 0.22)" style="position: absolute; top: 12px; left: 14px;">
                        <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/>
                    </svg>
                    <div id="modal-memory-body" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.05rem; line-height: 1.8; color: #2D2724; font-style: italic; position: relative; z-index: 1; white-space: pre-wrap; word-break: break-word;">
                        Texto completo del mensaje...
                    </div>
                </div>
            </div>

            <!-- Modal Footer Controls -->
            <div style="background: #FAF7F2; padding: 18px 28px; border-top: 1px solid rgba(197, 160, 89, 0.2); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" id="modal-memory-prev" class="btn btn--outline" style="padding: 8px 16px; font-size: 0.8rem; border-radius: 8px;">
                        ← Anterior
                    </button>
                    <button type="button" id="modal-memory-next" class="btn btn--outline" style="padding: 8px 16px; font-size: 0.8rem; border-radius: 8px;">
                        Siguiente →
                    </button>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="button" id="modal-memory-delete-btn" style="background: rgba(194, 123, 127, 0.12); color: #C27B7F; border: 1px solid rgba(194, 123, 127, 0.35); padding: 8px 14px; font-size: 0.8rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" title="Eliminar dedicatoria">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Eliminar
                    </button>
                    <a id="modal-memory-link" href="#" target="_blank" class="btn btn--outline" style="padding: 8px 16px; font-size: 0.8rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;" title="Abrir en pestaña nueva">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        Vista Nueva
                    </a>
                    <button type="button" id="modal-memory-copy-btn" class="btn btn--gold" style="padding: 8px 18px; font-size: 0.8rem; border-radius: 8px;">
                        Copiar Mensaje
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS PARA INTERACTIVIDAD EN EL PANEL -->
    <script>
        // Array con todos los mensajes del libro cargados desde PHP
        const guestbookData = <?= json_encode($guestbookMessages) ?>;
        let currentMemoryIndex = 0;

        // Sistema de Pestañas
        function activateTab(tabId) {
            document.querySelectorAll('.admin-dashboard__tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.admin-dashboard__tab-content').forEach(c => c.classList.remove('active'));

            const targetBtn = document.querySelector(`.admin-dashboard__tab-btn[data-tab="${tabId}"]`);
            const targetContent = document.getElementById(tabId);

            if (targetBtn && targetContent) {
                targetBtn.classList.add('active');
                targetContent.classList.add('active');
            }
        }

        document.querySelectorAll('.admin-dashboard__tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-tab');
                activateTab(targetId);
            });
        });

        // Activar pestaña desde la URL si viene ?tab= o #
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hashParam = window.location.hash ? window.location.hash.replace('#', '') : null;

        if (tabParam === 'tab-libro' || hashParam === 'tab-libro') {
            activateTab('tab-libro');
        }

        // Filtro en vivo para la tabla RSVP
        const searchRsvp = document.getElementById('search-rsvp');
        if (searchRsvp) {
            searchRsvp.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#table-rsvp tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }

        // Filtro en vivo para el libro de firmas
        const searchGuestbook = document.getElementById('search-guestbook');
        if (searchGuestbook) {
            searchGuestbook.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#table-guestbook tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }

        // --- MODAL DE LECTURA DE DEDICATORIAS ---
        const memoryModal = document.getElementById('memory-modal');
        const modalAuthor = document.getElementById('modal-memory-author');
        const modalDate = document.getElementById('modal-memory-date');
        const modalBody = document.getElementById('modal-memory-body');
        const modalCounter = document.getElementById('modal-memory-counter');
        const modalLink = document.getElementById('modal-memory-link');
        const modalPrevBtn = document.getElementById('modal-memory-prev');
        const modalNextBtn = document.getElementById('modal-memory-next');
        const modalCopyBtn = document.getElementById('modal-memory-copy-btn');
        const modalCloseBtn = document.getElementById('modal-memory-close-btn');
        const modalDeleteBtn = document.getElementById('modal-memory-delete-btn');

        function showMemoryAtIndex(index) {
            if (!guestbookData || guestbookData.length === 0) return;
            if (index < 0) index = 0;
            if (index >= guestbookData.length) index = guestbookData.length - 1;

            currentMemoryIndex = index;
            const item = guestbookData[index];

            if (modalAuthor) modalAuthor.textContent = item.author || 'Invitado/a';
            if (modalDate) modalDate.textContent = item.formatted_date || item.created_at || '';
            if (modalBody) modalBody.textContent = item.message || '';
            if (modalCounter) modalCounter.textContent = `${index + 1} de ${guestbookData.length}`;

            // Link a la vista dedicada en nueva pestaña
            if (modalLink) {
                modalLink.href = `/admin?recuerdo_id=${item.id}`;
            }

            // Habilitar / deshabilitar botones de navegación
            if (modalPrevBtn) modalPrevBtn.disabled = (index <= 0);
            if (modalNextBtn) modalNextBtn.disabled = (index >= guestbookData.length - 1);

            // Mostrar el modal
            if (memoryModal) {
                memoryModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeMemoryModal() {
            if (memoryModal) {
                memoryModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        // Eventos para abrir el modal desde la tabla
        document.querySelectorAll('#table-guestbook tbody tr.guestbook-row').forEach(row => {
            row.addEventListener('click', (e) => {
                const idx = parseInt(row.getAttribute('data-index'), 10);
                if (!isNaN(idx)) {
                    showMemoryAtIndex(idx);
                }
            });
        });

        document.querySelectorAll('.btn-open-memory').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation(); // Evitar doble click con el tr
                const idx = parseInt(btn.getAttribute('data-index'), 10);
                if (!isNaN(idx)) {
                    showMemoryAtIndex(idx);
                }
            });
        });

        // Función centralizada para eliminar dedicatoria
        async function executeDeleteMemory(id, author) {
            if (!id) return;
            const confirmed = confirm(`¿Estás seguro de que deseas eliminar permanentemente la dedicatoria de "${author || 'este invitado'}"?\n\nEsta acción no se puede deshacer.`);
            if (!confirmed) return;

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'delete_guestbook');
                formData.append('id', id);

                const res = await fetch('/admin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: formData.toString()
                });

                const data = await res.json();
                if (data.success) {
                    window.location.href = '/admin?tab=tab-libro';
                } else {
                    alert(data.message || 'No se pudo eliminar el mensaje.');
                }
            } catch (err) {
                // Fallback de formulario estándar si falla fetch
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin';
                form.innerHTML = `<input type="hidden" name="action" value="delete_guestbook"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Listener para botones de eliminar en la tabla
        document.querySelectorAll('.btn-delete-memory').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation(); // Evitar abrir el modal al presionar eliminar
                const id = btn.getAttribute('data-id');
                const author = btn.getAttribute('data-author');
                executeDeleteMemory(id, author);
            });
        });

        // Listener para botón de eliminar en el modal
        if (modalDeleteBtn) {
            modalDeleteBtn.addEventListener('click', () => {
                if (!guestbookData || !guestbookData[currentMemoryIndex]) return;
                const item = guestbookData[currentMemoryIndex];
                executeDeleteMemory(item.id, item.author);
            });
        }

        // Controles de navegación dentro del modal
        if (modalPrevBtn) {
            modalPrevBtn.addEventListener('click', () => {
                if (currentMemoryIndex > 0) {
                    showMemoryAtIndex(currentMemoryIndex - 1);
                }
            });
        }

        if (modalNextBtn) {
            modalNextBtn.addEventListener('click', () => {
                if (currentMemoryIndex < guestbookData.length - 1) {
                    showMemoryAtIndex(currentMemoryIndex + 1);
                }
            });
        }

        // Botón Copiar Mensaje
        if (modalCopyBtn) {
            modalCopyBtn.addEventListener('click', () => {
                const text = modalBody ? modalBody.textContent.trim() : '';
                if (!text) return;

                navigator.clipboard.writeText(text).then(() => {
                    const orig = modalCopyBtn.innerHTML;
                    modalCopyBtn.innerHTML = '<span>¡Copiado!</span>';
                    setTimeout(() => { modalCopyBtn.innerHTML = orig; }, 2000);
                }).catch(() => {
                    alert('No se pudo copiar automáticamente.');
                });
            });
        }

        // Cerrar modal
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', closeMemoryModal);
        }

        if (memoryModal) {
            memoryModal.addEventListener('click', (e) => {
                if (e.target === memoryModal) {
                    closeMemoryModal();
                }
            });
        }

        // Teclas de acceso rápido: Escape para cerrar, Flechas para navegar
        document.addEventListener('keydown', (e) => {
            if (!memoryModal || memoryModal.style.display !== 'flex') return;

            if (e.key === 'Escape') {
                closeMemoryModal();
            } else if (e.key === 'ArrowLeft' && currentMemoryIndex > 0) {
                showMemoryAtIndex(currentMemoryIndex - 1);
            } else if (e.key === 'ArrowRight' && currentMemoryIndex < guestbookData.length - 1) {
                showMemoryAtIndex(currentMemoryIndex + 1);
            }
        });
    </script>
</body>
</html>
