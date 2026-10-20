# 💍 Invitación OnePage - Despedida de Solteros Formal

Una página web moderna, romántica y de alta elegancia diseñada para la **Despedida de Solteros Formal** de **Alejandra & Jesús**, a celebrarse el **Martes, 27 de Octubre del 2026 a las 5:00 PM en el Salón Abako** (Hermosillo, Sonora).

Desarrollada aplicando principios de **Clean Code** y **Single Responsibility Principle (SRP)** con **PHP 8.2**, **MySQL**, **HTML5**, **SASS (SCSS)**, **JavaScript Modular**, **Composer** y un entorno completamente contenedorizado con **Docker**.

---

## ✨ Características Principales

1. **Estilo Romántico & Elegante**:
   - Paleta de colores de lujo: Marfil cálido (`#FAF7F2`), Oro clásico y champán (`#C5A059`), y detalles en rosa blush empolvado (`#C27B7F`).
   - Tipografía editorial: Combinación de *Cormorant Garamond* (serif refinada), *Great Vibes* (caligrafía) y *Plus Jakarta Sans* (lectura moderna).
   - Efecto sutil de partículas y pétalos flotantes en canvas que no interfieren con la lectura ni el rendimiento.
2. **Reloj de Cuenta Regresiva en Vivo**:
   - Muestra días, horas, minutos y segundos exactos calculados en tiempo real hasta el **Martes, 27 de octubre de 2026 a las 17:00:00 (5:00 PM)**.
3. **Lluvia de Sobres / Transferencia Bancaria**:
   - Muestra claramente los costos estipulados:
     - **Invitados:** **$350 MXN**
     - **Organizadores:** **$500 MXN**
   - Tarjeta con datos bancarios (Banco, Beneficiario, CLABE, Cuenta, Concepto).
   - Botón interactivo de **copiado al portapapeles con un solo clic** para la CLABE y el número de cuenta.
   - Enlace directo a WhatsApp para compartir el comprobante de transferencia.
   - Totalmente personalizable a través del archivo `.env`.
4. **Firma del Libro de Recuerdos (Guestbook)**:
   - Formulario elegante con:
     - Campo **"De parte de"** (Nombre de quien firma).
     - Contenedor de texto para **deseo, anécdota o recuerdo**.
     - Botón de envío con estado de carga ("Guardando con amor...").
   - Persistencia segura en base de datos **MySQL** (`guestbook_messages`).
   - Muro interactivo que muestra las dedicatorias recientes y las agrega al instante sin recargar la página.
5. **Confirmación de Asistencia (RSVP)**:
   - Formulario con Nombre, Apellidos, confirmación de asistencia, número de acompañantes y notas especiales (alergias/menú).
   - Almacenamiento seguro en **MySQL** (`rsvp_attendees`).
6. **Panel Administrativo Privado (`/admin.php`)**:
   - Acceso seguro mediante inicio de sesión con contraseña:
   - Métricas en tiempo real: Total personas confirmadas, respuestas recibidas, acompañantes y firmas.
   - Pestaña con **Lista completa de Asistentes** con buscador en tiempo real, enlace directo a WhatsApp y botón para **descargar en Excel (CSV)**.
   - Pestaña con **Libro de Recuerdos y Firmas** completo.

---

## 🏛️ Arquitectura de Software & Clean Code (SRP)

El código backend sigue el principio de responsabilidad única (Single Responsibility Principle) organizado mediante PSR-4:

```
Despedida/
├── bin/
│   └── compile-sass.php              # Script para compilar SASS a CSS
├── docker/
│   ├── apache.conf                   # Configuración de VirtualHost con mod_rewrite
│   └── mysql/
│       └── init.sql                  # Script SQL de creación de tablas y datos semilla
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css             # CSS compilado
│   │   ├── js/
│   │   │   ├── countdown.js          # Reloj cuenta regresiva en vivo
│   │   │   ├── guestbook.js          # Lógica AJAX del libro de firmas
│   │   │   ├── rsvp.js               # Lógica AJAX de confirmación RSVP
│   │   │   └── main.js               # Canvas de pétalos, portapapeles y toasts
│   │   └── scss/
│   │       ├── _variables.scss       # Paleta, fuentes y sombras
│   │       ├── _mixins.scss          # Flexbox, media queries y botones
│   │       ├── _base.scss            # Reset y tipografía
│   │       ├── _components.scss      # Tarjetas, botones y formularios
│   │       ├── _sections.scss        # Hero, countdown, detalles, sobre, libro
│   │       └── style.scss            # Importador principal de SASS
│   ├── .htaccess                     # Reescritura para URLs amigables
│   ├── api.php                       # Router REST para endpoints JSON
│   └── index.php                     # Punto de entrada y plantilla One-Page
├── src/
│   ├── Config/
│   │   └── Database.php              # Conexión PDO Singleton a MySQL
│   ├── Controllers/
│   │   ├── GuestbookController.php   # Manejo de peticiones HTTP para el libro de firmas
│   │   └── RsvpController.php        # Manejo de peticiones HTTP para RSVP
│   ├── Models/
│   │   ├── GuestbookMessage.php      # Entidad de mensaje del libro
│   │   └── RsvpResponse.php          # Entidad de confirmación RSVP
│   ├── Repositories/
│   │   ├── GuestbookRepository.php   # Consultas SQL preparadas (PDO) para libro
│   │   └── RsvpRepository.php        # Consultas SQL preparadas (PDO) para RSVP
│   └── Services/
│       ├── GuestbookService.php      # Validación y protección anti-XSS para firmas
│       └── RsvpService.php           # Validación de reglas de negocio para RSVP
├── .env.example                      # Plantilla de variables de entorno
├── .env                              # Variables activas del entorno
├── composer.json                     # Autoloader PSR-4 y dependencias
├── docker-compose.yml                # Orquestación de contenedores (App + MySQL)
├── Dockerfile                        # Imagen de PHP 8.2 + Apache + extensiones
└── README.md                         # Documentación del proyecto
```

### Principios aplicados:
* **Seguridad:** Consultas SQL parametrizadas con **PDO Prepared Statements** para prevenir inyección SQL. Saneamiento contra **XSS** mediante `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
* **Desacoplamiento:** Controladores solo reciben peticiones HTTP; Servicios aplican validaciones de negocio; Repositorios interactúan con la base de datos; Modelos representan entidades de dominio.

---

