-- ============================================================
-- BASE DE DATOS: despedida_db
-- Inicialización de tablas y datos semilla
-- ============================================================

CREATE DATABASE IF NOT EXISTS `despedida_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `despedida_db`;

-- ------------------------------------------------------------
-- TABLA: guestbook_messages (Firma de Libro / Recuerdos)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `guestbook_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author` VARCHAR(150) NOT NULL COMMENT 'De parte de (Nombre de quien firma)',
    `message` TEXT NOT NULL COMMENT 'Deseo, anécdota o recuerdo para los novios',
    `is_approved` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Visible, 0 = Oculto',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Libro de firmas y mejores deseos';

-- ------------------------------------------------------------
-- TABLA: rsvp_attendees (Confirmaciones de Asistencia)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsvp_attendees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL COMMENT 'Nombre del invitado',
    `last_name` VARCHAR(100) NOT NULL COMMENT 'Apellido del invitado',
    `attendance_status` ENUM('confirmed', 'declined') NOT NULL DEFAULT 'confirmed' COMMENT 'Estado de asistencia',
    `role_type` ENUM('invitado', 'organizador') NOT NULL DEFAULT 'invitado' COMMENT 'Tipo de asistente (Invitado $350 / Organizador $500)',
    `companions` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Número de acompañantes',
    `phone` VARCHAR(30) NULL COMMENT 'Teléfono de contacto opcional',
    `notes` VARCHAR(255) NULL COMMENT 'Mensaje especial o alergias',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rsvp_name` (`last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de confirmaciones RSVP';

-- ------------------------------------------------------------
-- DATOS SEMILLA (Mensajes de ejemplo para el Libro de Firmas)
-- ------------------------------------------------------------
INSERT INTO `guestbook_messages` (`author`, `message`, `created_at`) VALUES
('Familia Morales Gómez', '¡Les deseamos toda la felicidad del mundo en esta nueva etapa que están por comenzar! Qué honor celebrar con ustedes.', NOW() - INTERVAL 2 DAY),
('Mariana & Carlos', '¡Nos llena de emoción acompañarlos en su despedida! Que el amor y la complicidad siempre iluminen su camino. ¡A disfrutar la noche!', NOW() - INTERVAL 1 DAY),
('Sofía Rivas (Dama de Honor)', 'Inolvidables recuerdos desde la universidad y verlos hoy construir este sueño juntos me llena el corazón. ¡Los quiero muchísimo!', NOW() - INTERVAL 3 HOUR);

-- ------------------------------------------------------------
-- TABLA: admin_users (Organizadores con acceso al panel)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(60) NOT NULL UNIQUE COMMENT 'Nombre de usuario único',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada con algoritmo Bcrypt',
    `full_name` VARCHAR(120) NULL COMMENT 'Nombre completo del organizador',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Activo, 0 = Inactivo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios organizadores con acceso al panel';

-- Inserción de usuarios iniciales con contraseña encriptada (Bcrypt)
INSERT INTO `admin_users` (`username`, `password_hash`, `full_name`, `is_active`) VALUES
('luisagalazmp', '$2y$10$Hro.EkEZl2J1tdeojMt3v.iOdM.ESz5YFI7AKH/XUHtR4YLDmCen.', 'Luisa Galaz', 1),
('itava96', '$2y$10$8QTevBYSAjDkmpGTnFLGJ.ai9GdirstfRzrVbnleYqOcBgejL4BbK', 'Itzel Valencia', 1)
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `full_name` = VALUES(`full_name`);
