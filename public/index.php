<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Database;
use App\Repositories\GuestbookRepository;
use App\Repositories\RsvpRepository;
use App\Services\GuestbookService;
use App\Services\RsvpService;
use Dotenv\Dotenv;

// Cargar variables de entorno
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env') && class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable($envPath);
    $dotenv->safeLoad();
}

// Variables configurables del evento
$coupleNames      = $_ENV['EVENT_COUPLE'] ?? '';
$eventDate        = $_ENV['EVENT_DATE'] ?? '';
$eventTime        = $_ENV['EVENT_TIME'] ?? '';
$eventVenue       = $_ENV['EVENT_VENUE'] ?? '';
$eventAddress     = $_ENV['EVENT_LOCATION_ADDRESS'] ?? '';
$eventMapsUrl     = $_ENV['EVENT_MAPS_URL'] ?? '';

$costGuest        = $_ENV['COSTO_INVITADO'] ?? 350;
$costOrganizer    = $_ENV['COSTO_ORGANIZADOR'] ?? 500;
$maxAttendees     = (int)($_ENV['MAX_ATTENDEES'] ?? 100);

// Extraer nombres individuales para el diseño
$namesArray = preg_split('/\s+(?:y|&)\s+/i', $coupleNames);
$brideName  = $namesArray[0] ?? 'Alejandra';
$groomName  = $namesArray[1] ?? 'Jesús';

// Datos de transferencia bancaria
$bankName         = $_ENV['BANK_NAME'] ?? '';
$bankBeneficiary  = $_ENV['BANK_BENEFICIARY'] ?? '';
$bankClabe        = $_ENV['BANK_CLABE'] ?? '';
$bankAccount      = $_ENV['BANK_ACCOUNT'] ?? '';
$bankConcept      = $_ENV['BANK_CONCEPT'] ?? '';
$bankWhatsapp     = $_ENV['BANK_WHATSAPP'] ?? '';

// Variables de estado de asistencia y libro de firmas
$initialMessages = [];
$confirmedCount  = 0;
$isCapacityFull  = false;
$availableSpots  = $maxAttendees;
$dbConnected     = false;

try {
    $db = Database::getConnection();

    // Mensajes del libro de firmas
    $guestbookRepo = new GuestbookRepository($db);
    $guestbookService = new GuestbookService($guestbookRepo);
    $initialMessages = $guestbookService->getRecentMessages(20);

    // Estado del cupo de asistencia (Límite 100 personas)
    $rsvpRepo = new RsvpRepository($db);
    $rsvpService = new RsvpService($rsvpRepo, $maxAttendees);
    $confirmedCount = $rsvpService->getConfirmedCount();
    $isCapacityFull = $rsvpService->isCapacityFull();
    $availableSpots = $rsvpService->getAvailableSpots();

    $dbConnected = true;
} catch (\Throwable $e) {
    error_log("Aviso: Conexión a BD en index.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despedida de Solteros | <?= htmlspecialchars($brideName) ?> y <?= htmlspecialchars($groomName) ?></title>
    <meta name="description" content="Invitación formal y libro de recuerdos a la despedida de solteros de <?= htmlspecialchars($brideName) ?> y <?= htmlspecialchars($groomName) ?> en el Salón Abako.">

    <!-- Favicon e Íconos Oficiales (Monograma A&J) -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">

    <!-- Tipografías de Google Fonts: Serif romántica (Cormorant Garamond), Caligrafía (Great Vibes) y Sans legible (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Great+Vibes&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Hoja de estilos compilada desde SASS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Canvas de partículas y pétalos románticos flotantes -->
    <canvas id="petals-canvas"></canvas>

    <!-- ==========================================
         BARRA DE NAVEGACIÓN
         ========================================== -->
    <header class="navbar">
        <div class="container navbar__content">
            <a href="#inicio" class="navbar__logo" style="display: inline-flex; align-items: center; gap: 10px;">

                <span><?= htmlspecialchars($brideName) ?> <span>&</span> <?= htmlspecialchars($groomName) ?></span>
            </a>
            <nav>
                <ul class="navbar__menu">
                    <li><a href="#inicio" class="navbar__link">Inicio</a></li>
                    <li><a href="#cuenta-regresiva" class="navbar__link">Cuenta Regresiva</a></li>
                    <li><a href="#momentos" class="navbar__link">Momentos</a></li>
                    <li><a href="#detalles" class="navbar__link">Detalles</a></li>
                    <li><a href="#sobre" class="navbar__link">El Sobre</a></li>
                    <li><a href="#asistencia" class="navbar__link">Confirmar</a></li>
                    <li><a href="#libro-firmas" class="navbar__link">Libro de Firmas</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- ==========================================
         SECCIÓN HERO / PORTADA
         ========================================== -->
    <main>
        <section id="inicio" class="hero">
            <div class="container">
                <div class="hero__grid">
                    <!-- Columna de Texto -->
                    <div class="hero__content">
                        <div class="hero__badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            Despedida de Solteros Formal
                        </div>

                        <div class="hero__subtitle-top">Nuestra Historia de Amor</div>
                        <h1 class="hero__names">
                            <?= htmlspecialchars($brideName) ?>
                            <span class="ampersand">&</span>
                            <?= htmlspecialchars($groomName) ?>
                        </h1>

                        <p class="hero__quote">
                            "El amor no se mira con los ojos, sino con el alma. Los esperamos para celebrar juntos el inicio de este nuevo y maravilloso capítulo."
                        </p>

                        <div class="hero__meta">
                            <div class="hero__meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>Martes, 27 de Octubre de 2026</span>
                            </div>
                            <span class="hero__meta-divider"></span>
                            <div class="hero__meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>5:00 PM</span>
                            </div>
                            <span class="hero__meta-divider"></span>
                            <div class="hero__meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span><?= htmlspecialchars($eventVenue) ?></span>
                            </div>
                        </div>

                        <div class="hero__actions">
                            <a href="#asistencia" class="btn btn--gold">Confirmar Asistencia</a>
                            <a href="#libro-firmas" class="btn btn--outline">Firmar Libro de Recuerdos</a>
                        </div>
                    </div>

                    <!-- Columna de Retrato Editorial con Marco de Arco -->
                    <div class="hero__photo-frame">
                        <img src="assets/images/couple-kiss-moon.jpg" alt="<?= htmlspecialchars($brideName) ?> y <?= htmlspecialchars($groomName) ?>" loading="eager">
                        <div class="hero__photo-frame-tag">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                            <?= htmlspecialchars($brideName) ?> & <?= htmlspecialchars($groomName) ?> • 2026
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             RELOJ CON CUENTA REGRESIVA EN VIVO
             ========================================== -->
        <section id="cuenta-regresiva" class="countdown-section" data-target-date="<?= htmlspecialchars($eventDate) ?>T<?= htmlspecialchars($eventTime) ?>">
            <div class="container">
                <div class="countdown-section__header">
                    <span class="section-header__tag">Cuenta Regresiva</span>
                    <h3>Faltan tan solo...</h3>
                    <p id="cd-status">Contando cada segundo hasta encontrarnos en el <?= htmlspecialchars($eventVenue) ?></p>
                </div>

                <div class="countdown-section__grid">
                    <div class="countdown-section__card">
                        <div class="countdown-section__number" id="cd-days">--</div>
                        <div class="countdown-section__label">Días</div>
                    </div>
                    <div class="countdown-section__card">
                        <div class="countdown-section__number" id="cd-hours">--</div>
                        <div class="countdown-section__label">Horas</div>
                    </div>
                    <div class="countdown-section__card">
                        <div class="countdown-section__number" id="cd-minutes">--</div>
                        <div class="countdown-section__label">Minutos</div>
                    </div>
                    <div class="countdown-section__card">
                        <div class="countdown-section__number" id="cd-seconds">--</div>
                        <div class="countdown-section__label">Segundos</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN GALERÍA / NUESTROS MOMENTOS
             ========================================== -->
        <section id="momentos" class="gallery-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-header__tag">Nuestra Historia</span>
                    <h2 class="section-header__title">Momentos Inolvidables</h2>
                    <div class="section-header__divider">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </div>
                    <p class="section-header__subtitle">
                        Cada risa, cada aventura y cada sueño compartido nos ha traído hasta este momento tan esperado.
                    </p>
                </div>

                <div class="gallery-section__grid">
                    <!-- Foto 1: Bajo la Luna y el Mar -->
                    <div class="gallery-section__item">
                        <img src="assets/images/couple-kiss-moon.jpg" alt="Bajo la luna y el mar" loading="lazy">
                        <div class="gallery-section__overlay">
                            <h3 class="gallery-section__overlay-title">Bajo la Luna</h3>
                            <p class="gallery-section__overlay-desc">Un beso frente al mar</p>
                        </div>
                    </div>

                    <!-- Foto 2: Al Ritmo de Nuestro Amor -->
                    <div class="gallery-section__item">
                        <img src="assets/images/couple-dance-sunset.jpg" alt="Al ritmo de nuestro amor" loading="lazy">
                        <div class="gallery-section__overlay">
                            <h3 class="gallery-section__overlay-title">Bailando Juntos</h3>
                            <p class="gallery-section__overlay-desc">Al ritmo de nuestro amor</p>
                        </div>
                    </div>

                    <!-- Foto 3: Nuestro Reflejo -->
                    <div class="gallery-section__item">
                        <img src="assets/images/couple-mirror-reflection.jpg" alt="Nuestro reflejo" loading="lazy">
                        <div class="gallery-section__overlay">
                            <h3 class="gallery-section__overlay-title">Nuestro Reflejo</h3>
                            <p class="gallery-section__overlay-desc">Un amor que trasciende</p>
                        </div>
                    </div>

                    <!-- Foto 4: Cómplices de Vida -->
                    <div class="gallery-section__item">
                        <img src="assets/images/couple-guitar-archery.jpg" alt="Cómplices de vida" loading="lazy">
                        <div class="gallery-section__overlay">
                            <h3 class="gallery-section__overlay-title">Cómplices de Vida</h3>
                            <p class="gallery-section__overlay-desc">Pasión, risas y aventuras</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             DETALLES DEL EVENTO
             ========================================== -->
        <section id="detalles" class="details-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-header__tag">Información</span>
                    <h2 class="section-header__title">Detalles de la Celebración</h2>
                    <div class="section-header__divider">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </div>
                    <p class="section-header__subtitle">
                        Hemos preparado una velada llena de momentos inolvidables, buena música y mucha alegría para compartir.
                    </p>
                </div>

                <div class="details-section__grid">
                    <!-- Tarjeta 1: Fecha y Hora -->
                    <div class="details-section__card">
                        <div class="details-section__card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <h3 class="details-section__card-title">Fecha & Horario</h3>
                        <p class="details-section__card-desc">
                            <strong>Martes 27 de Octubre del 2026</strong><br>
                            Recepción y bienvenida: <strong>5:00 PM</strong><br>
                            Cena y velada inolvidable.
                        </p>
                    </div>

                    <!-- Tarjeta 2: Ubicación & Salón Abako -->
                    <div class="details-section__card">
                        <div class="details-section__card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <h3 class="details-section__card-title"><?= htmlspecialchars($eventVenue) ?></h3>
                        <p class="details-section__card-desc">
                            <?= htmlspecialchars($eventAddress) ?>
                        </p>
                        <a href="<?= htmlspecialchars($eventMapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--gold" style="padding: 10px 24px; font-size: 0.85rem;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            Abrir en Google Maps
                        </a>
                    </div>
                </div>

                <!-- Mapa Interactivo de Google Maps -->
                <div class="details-section__map-wrapper">
                    <iframe
                        src="https://maps.google.com/maps?q=C.+de+la+Reforma+158,+San+Benito,+83190+Hermosillo,+Son.&t=&z=16&ie=UTF8&iwloc=&output=embed"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Ubicación Salón Abako Hermosillo">
                    </iframe>
                </div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN TRANSFERENCIA PARA EL SOBRE
             ========================================== -->
        <section id="sobre" class="transfer-section">
            <div class="container container--narrow">
                <div class="section-header">
                    <span class="section-header__tag">Lluvia de Sobres</span>
                    <h2 class="section-header__title">Transferencia o Sobre</h2>
                    <div class="section-header__divider">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </div>
                    <p class="section-header__subtitle">
                        Su presencia es nuestro regalo más valioso en esta velada tan especial. Si desean acompañarnos con un detalle, hemos dispuesto la opción de lluvia de sobres, ya sea mediante un <strong>sobre tradicional</strong> o a través de una <strong>transferencia bancaria</strong>.
                    </p>
                </div>

                <!-- Tarjetas con los costos especificados -->
                <div class="transfer-section__prices">
                    <div class="transfer-section__price-card">
                        <div class="transfer-section__price-card-role">Invitados</div>
                        <div class="transfer-section__price-card-amount">$<?= number_format((float)$costGuest, 0) ?> <span>MXN</span></div>
                        <div class="transfer-section__price-card-desc">Por persona (Incluye cena gourmet, bebidas y velada especial)</div>
                    </div>

                    <div class="transfer-section__price-card transfer-section__price-card--featured">
                        <span class="transfer-section__price-card-badge">Comité</span>
                        <div class="transfer-section__price-card-role">Organizadores</div>
                        <div class="transfer-section__price-card-amount">$<?= number_format((float)$costOrganizer, 0) ?> <span>MXN</span></div>
                        <div class="transfer-section__price-card-desc">Aportación para organizadores y dinámicas especiales</div>
                    </div>
                </div>

                <!-- Tarjeta con los datos bancarios y botón de copia con un clic -->
                <div class="transfer-section__box">
                    <div class="transfer-section__item">
                        <span class="transfer-section__item-label">Banco</span>
                        <div class="transfer-section__item-value" id="bank-name-val"><?= htmlspecialchars($bankName) ?></div>
                    </div>

                    <div class="transfer-section__item">
                        <span class="transfer-section__item-label">Beneficiario</span>
                        <div class="transfer-section__item-value" id="bank-bene-val"><?= htmlspecialchars($bankBeneficiary) ?></div>
                    </div>

                    <div class="transfer-section__item">
                        <span class="transfer-section__item-label">CLABE Interbancaria</span>
                        <div class="transfer-section__item-value">
                            <span id="bank-clabe-val" data-copy-value="<?= htmlspecialchars($bankClabe) ?>"><?= htmlspecialchars($bankClabe) ?></span>
                            <button type="button" class="btn--copy" data-copy-target="bank-clabe-val" title="Copiar CLABE">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Copiar
                            </button>
                        </div>
                    </div>

                    <div class="transfer-section__item">
                        <span class="transfer-section__item-label">Número de Cuenta</span>
                        <div class="transfer-section__item-value">
                            <span id="bank-acc-val" data-copy-value="<?= htmlspecialchars($bankAccount) ?>"><?= htmlspecialchars($bankAccount) ?></span>
                            <button type="button" class="btn--copy" data-copy-target="bank-acc-val" title="Copiar Cuenta">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Copiar
                            </button>
                        </div>
                    </div>

                    <div class="transfer-section__item">
                        <span class="transfer-section__item-label">Concepto Sugerido</span>
                        <div class="transfer-section__item-value"><?= htmlspecialchars($bankConcept) ?></div>
                    </div>

                    <div class="transfer-section__item">
                        <span class="transfer-section__item-label">Mandar Comprobante</span>
                        <div class="transfer-section__item-value">
                            <span>WhatsApp: <strong>662 174 8930</strong></span>
                        </div>
                    </div>

                    <?php if (!empty($bankWhatsapp)): ?>
                        <div style="text-align: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid rgba(197, 160, 89, 0.2);">
                            <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $bankWhatsapp)) ?>?text=<?= urlencode('¡Hola! Te comparto mi comprobante para la despedida de solteros de Alejandra y Jesús.') ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.541 1.961.826 2.796.826 3.183 0 5.77-2.587 5.77-5.768 0-3.181-2.587-5.769-5.77-5.769zm3.364 8.205c-.148.417-.864.814-1.204.856-.34.043-.76.06-2.228-.549-1.854-.77-3.036-2.656-3.129-2.779-.092-.123-.748-.996-.748-1.9 0-.904.474-1.348.643-1.533.169-.185.37-.231.493-.231.123 0 .247.001.354.006.113.005.263-.043.411.314.154.37.525 1.28.571 1.373.046.092.077.2.015.323-.062.124-.092.201-.185.309-.092.108-.195.241-.278.324-.093.093-.19.195-.082.38.108.185.481.794 1.032 1.284.71.633 1.309.829 1.494.922.185.093.293.077.401-.046.108-.124.463-.54.586-.725.123-.186.247-.154.417-.093.17.062 1.079.509 1.264.602.185.092.308.139.354.216.047.078.047.448-.101.865z" />
                                </svg>
                                Enviar Comprobante por WhatsApp (662 174 8930)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN CONFIRMAR ASISTENCIA (RSVP)
             ========================================== -->
        <section id="asistencia" class="rsvp-section">
            <div class="container rsvp-section__wrapper">
                <div class="section-header">
                    <span class="section-header__tag">RSVP</span>
                    <h2 class="section-header__title">Confirma tu Asistencia</h2>
                    <div class="section-header__divider">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </div>
                    <p class="section-header__subtitle">
                        Por favor confírmanos antes del 25 de Octubre de 2026 para garantizar la mejor organización de lugares y banquete.
                    </p>
                    <div style="margin-top: 14px; display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                        <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #8E4A49; font-weight: 600; background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.35); padding: 6px 18px; border-radius: 30px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Cupo Limitado: <?= $maxAttendees ?> personas (<?= $isCapacityFull ? '<strong style="color: #c0392b;">Cupo Lleno</strong>' : $availableSpots . ' lugares disponibles' ?>)
                        </span>

                        <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #3A2E39; font-weight: 600; background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.35); padding: 6px 16px; border-radius: 30px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Invitación Mixta • Solo Mayores de Edad (+18)
                        </span>
                    </div>
                </div>

                <!-- Contenedor Blanco del Formulario RSVP -->
                <div class="card" style="background: #FFFFFF; border-radius: 24px; border: 1.5px solid rgba(197, 160, 89, 0.3); box-shadow: 0 15px 40px rgba(60, 48, 38, 0.08); padding: 36px 32px;">

                    <!-- Estado Visual de Confirmación Exitosa -->
                    <div id="rsvp-success-state" class="rsvp-success-state" style="display: none; padding: 36px 20px; text-align: center;">
                        <div id="rsvp-success-icon-box" style="width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(42, 157, 143, 0.14); border: 2px solid #2A9D8F; color: #2A9D8F;">
                            <svg id="rsvp-success-icon-check" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <svg id="rsvp-success-icon-heart" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        </div>

                        <span id="rsvp-success-badge" style="display: inline-block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #2A9D8F; margin-bottom: 6px;">
                            ¡Confirmación Recibida!
                        </span>

                        <h3 id="rsvp-success-title" style="font-family: 'Cormorant Garamond', serif; font-size: 2rem; color: #2D2724; margin-bottom: 14px; font-weight: 700; line-height: 1.2;">
                            ¡Gracias por confirmar tu asistencia!
                        </h3>

                        <div id="rsvp-success-text" style="font-size: 1.05rem; line-height: 1.7; color: #4A3E3D; max-width: 520px; margin: 0 auto 26px; padding: 18px 24px; background: rgba(197, 160, 89, 0.08); border-radius: 14px; border: 1px solid rgba(197, 160, 89, 0.25);">
                            <!-- Mensaje devuelto por el servidor -->
                        </div>

                        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                            <button type="button" id="rsvp-reset-btn" class="btn btn--gold" style="padding: 12px 28px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.92rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                Registrar otra asistencia
                            </button>
                        </div>
                    </div>

                    <!-- Banner de Cupo Completo -->
                    <div id="rsvp-capacity-banner" style="<?= $isCapacityFull ? 'display: block;' : 'display: none;' ?> background: rgba(142, 74, 73, 0.08); border: 2px dashed #8E4A49; border-radius: 12px; padding: 30px 20px; text-align: center; margin-bottom: 20px;">
                        <div style="width: 52px; height: 52px; margin: 0 auto 12px; background: rgba(142, 74, 73, 0.14); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#8E4A49" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <h3 style="color: #8E4A49; font-size: 1.4rem; margin-bottom: 8px; font-family: 'Cormorant Garamond', serif; font-weight: 700;">
                            ¡Cupo Máximo Alcanzado!
                        </h3>
                        <p style="color: #4A3E3D; font-size: 0.95rem; line-height: 1.6; margin: 0 auto; max-width: 520px;">
                            Agradecemos profundamente todo el amor e interés en acompañarnos. Se ha cubierto el límite máximo de <strong><?= $maxAttendees ?> personas</strong> registradas para la despedida.
                        </p>
                    </div>

                    <form id="rsvp-form" class="form" method="POST" action="/api/rsvp" <?= $isCapacityFull ? 'style="display: none;"' : '' ?>>
                        <div class="rsvp-inputs-wrapper" style="background: #FFFFFF; border-radius: 18px; margin-bottom: 10px;">
                            <div class="form__row">
                                <div class="form__group">
                                    <label for="rsvp-first-name" class="form__label">Nombre <span class="required">*</span></label>
                                    <input type="text" id="rsvp-first-name" name="first_name" class="form__control" style="background: #FFFFFF;" placeholder="Ej. Carlos" required>
                                </div>
                                <div class="form__group">
                                    <label for="rsvp-last-name" class="form__label">Apellido(s) <span class="required">*</span></label>
                                    <input type="text" id="rsvp-last-name" name="last_name" class="form__control" style="background: #FFFFFF;" placeholder="Ej. Morales Pérez" required>
                                </div>
                            </div>

                            <div class="form__row">
                                <div class="form__group">
                                    <label for="rsvp-status" class="form__label">¿Asistirás al evento? <span class="required">*</span></label>
                                    <select id="rsvp-status" name="attendance_status" class="form__control form__select" style="background-color: #FFFFFF;" required>
                                        <option value="confirmed" selected>¡Sí, con mucho gusto ahí estaré!</option>
                                        <option value="declined">Lamentablemente no podré asistir</option>
                                    </select>
                                </div>

                                <div class="form__group">
                                    <label for="rsvp-phone" class="form__label">Teléfono / WhatsApp</label>
                                    <input type="tel" id="rsvp-phone" name="phone" class="form__control" style="background: #FFFFFF;" placeholder="Ej. 662 123 4567">
                                </div>
                            </div>
                        </div>

                        <!-- Nota de Invitación Mixta y Solo Mayores de Edad -->
                        <div style="background: rgba(197, 160, 89, 0.12); border-left: 3px solid #C5A059; border-radius: 8px; padding: 12px 16px; margin: 8px 0 22px; font-size: 0.9rem; color: #3A2E39; display: flex; align-items: center; gap: 10px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#C5A059" stroke-width="2" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span><strong>Nota importante:</strong> La invitación es mixta y el evento es exclusivamente para mayores de edad (+18).</span>
                        </div>

                        <button type="submit" id="rsvp-submit-btn" class="btn btn--gold btn--full">
                            Confirmar Asistencia
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN FIRMA DE LIBRO (GUESTBOOK)
             ========================================== -->
        <section id="libro-firmas" class="guestbook-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-header__tag">Recuerdos</span>
                    <h2 class="section-header__title">Libro de Firmas & Deseos</h2>
                    <div class="section-header__divider">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </div>
                    <p class="section-header__subtitle">
                        Déjanos un mensaje con tu firma, un deseo sincero, una anécdota divertida o un recuerdo que atesoraremos por siempre.
                    </p>
                </div>

                <div class="guestbook-section__wrapper">
                    <!-- Formulario de Firma de Libro -->
                    <div class="card">
                        <form id="guestbook-form" class="form" method="POST" action="/api/guestbook">
                            <!-- Input "De parte de" -->
                            <div class="form__group">
                                <label for="guestbook-author" class="form__label">
                                    De parte de <span class="required">*</span>
                                </label>
                                <input type="text" id="guestbook-author" name="author" class="form__control" placeholder="Tu nombre, apodo o familia (ej. Familia Ramírez)" required maxlength="150">
                                <span class="form__hint">¿Quién firma esta dedicatoria?</span>
                            </div>

                            <!-- Contenedor de texto para deseo, anécdota o recuerdo -->
                            <div class="form__group">
                                <label for="guestbook-message" class="form__label">
                                    Tu Deseo, Anécdota o Recuerdo <span class="required">*</span>
                                </label>
                                <textarea id="guestbook-message" name="message" class="form__control form__textarea" placeholder="Escribe aquí unas palabras llenas de amor, algún recuerdo memorable que compartimos o tus mejores bendiciones para Alejandra y Jesús..." required maxlength="2000"></textarea>
                                <span class="form__hint">Máximo 2000 caracteres.</span>
                            </div>

                            <!-- Botón de Submit -->
                            <button type="submit" id="guestbook-submit-btn" class="btn btn--gold btn--full">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
                                    <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
                                    <path d="M2 2l7.586 7.586"></path>
                                    <circle cx="11" cy="11" r="2"></circle>
                                </svg>
                                Firmar Libro de Recuerdos
                            </button>
                        </form>
                    </div>

                    <!-- Muro de Mensajes / Firmas en Vivo -->
                    <div>
                        <h3 style="font-size: 1.4rem; color: #2D2724; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#C5A059">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                            Muro de Firmas Recientes
                        </h3>

                        <div class="guestbook-section__wall" id="guestbook-wall">
                            <?php if (empty($initialMessages)): ?>
                                <div class="guestbook-section__empty" id="guestbook-empty">
                                    Sé el primero en firmar nuestro libro de recuerdos. ¡Nos encantará leerte!
                                </div>
                            <?php else: ?>
                                <?php foreach ($initialMessages as $msg): ?>
                                    <article class="guestbook-section__entry">
                                        <div class="guestbook-section__entry-header">
                                            <h4 class="guestbook-section__entry-author">
                                                <svg viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                                </svg>
                                                <?= htmlspecialchars($msg->getAuthor()) ?>
                                            </h4>
                                            <time class="guestbook-section__entry-date"><?= htmlspecialchars($msg->getFormattedDate()) ?></time>
                                        </div>
                                        <p class="guestbook-section__entry-message">"<?= nl2br(htmlspecialchars($msg->getMessage())) ?>"</p>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==========================================
         FOOTER
         ========================================== -->
    <footer class="footer">
        <div class="container">
            <div class="footer__names">
                <?= htmlspecialchars($brideName) ?> <span>&</span> <?= htmlspecialchars($groomName) ?>
            </div>
            <p class="footer__quote">
                "Gracias de todo corazón por ser parte de nuestra historia y acompañarnos en este día tan especial."
            </p>
            <div class="footer__copy">
                &copy; 2026 Despedida de Solteros Formal • Salón Abako • Alejandra & Jesús
                <br>
                <a href="/admin" style="color: rgba(250, 247, 242, 0.45); font-size: 0.75rem; text-decoration: underline; margin-top: 6px; display: inline-block;">Acceso Organizadores (Admin)</a>
            </div>
        </div>
    </footer>

    <!-- Contenedor para notificaciones Toast -->
    <div class="toast-container" aria-live="polite"></div>

    <!-- Scripts modulares JS -->
    <script>
        // Configuración de URL base para la API
        window.API_BASE_URL = '';
    </script>
    <script src="assets/js/countdown.js"></script>
    <script src="assets/js/guestbook.js"></script>
    <script src="assets/js/rsvp.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>