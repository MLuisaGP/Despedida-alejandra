/**
 * Main Application Module
 * 
 * Responsabilidades:
 * 1. Inicialización de utilidades globales (Toast notifications).
 * 2. Portapapeles interactivo (Copiar cuenta / CLABE bancaria con un clic).
 * 3. Animación de pétalos suaves / polvo de oro en canvas de fondo.
 * 4. Navegación fluida y eventos generales de la One-Page.
 */

// --- TOAST NOTIFICATIONS HELPER ---
window.showToast = function(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
        <span class="toast__text">${escapeText(message)}</span>
    `;

    container.appendChild(toast);

    // Animación de entrada
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    // Auto-remover tras 4.5 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4500);
};

function escapeText(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

// --- COPIAR AL PORTAPAPELES (TRANSFERENCIA) ---
function initClipboardCopy() {
    const copyButtons = document.querySelectorAll('[data-copy-target]');

    copyButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const targetId = button.getAttribute('data-copy-target');
            const targetEl = document.getElementById(targetId);

            if (!targetEl) return;

            const textToCopy = targetEl.getAttribute('data-copy-value') || targetEl.textContent.trim();

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(textToCopy);
                } else {
                    // Fallback para contextos no HTTPS locales
                    const textArea = document.createElement('textarea');
                    textArea.value = textToCopy;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    textArea.style.top = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand('copy');
                    textArea.remove();
                }

                const originalText = button.innerHTML;
                button.classList.add('copied');
                button.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    ¡Copiado!
                `;

                window.showToast?.(`"${textToCopy}" copiado al portapapeles.`, 'success');

                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = originalText;
                }, 2500);

            } catch (err) {
                console.error('Error al copiar:', err);
                window.showToast?.('No fue posible copiar el texto automáticamente.', 'error');
            }
        });
    });
}

// --- ANIMACIÓN ROMÁNTICA DE PÉTALOS EN CANVAS ---
function initPetalsCanvas() {
    const canvas = document.getElementById('petals-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = canvas.width = window.innerWidth;
    let height = canvas.height = window.innerHeight;

    window.addEventListener('resize', () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });

    const petals = [];
    const maxPetals = 22; // Cantidad sutil y elegante

    class Petal {
        constructor() {
            this.reset();
        }

        reset() {
            this.x = Math.random() * width;
            this.y = -20;
            this.size = Math.random() * 8 + 6;
            this.speedY = Math.random() * 0.9 + 0.4;
            this.speedX = Math.random() * 0.6 - 0.3;
            this.rotation = Math.random() * 360;
            this.rotSpeed = Math.random() * 1 - 0.5;
            this.opacity = Math.random() * 0.45 + 0.15;
            // Alternar entre oro suave y rosa blush
            this.isGold = Math.random() > 0.4;
        }

        update() {
            this.y += this.speedY;
            this.x += Math.sin(this.y * 0.01) * 0.6 + this.speedX;
            this.rotation += this.rotSpeed;

            if (this.y > height + 20 || this.x < -20 || this.x > width + 20) {
                this.reset();
            }
        }

        draw() {
            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate((this.rotation * Math.PI) / 180);
            ctx.globalAlpha = this.opacity;

            if (this.isGold) {
                ctx.fillStyle = '#E3C888';
            } else {
                ctx.fillStyle = '#E8B6BA';
            }

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.quadraticCurveTo(this.size / 2, -this.size / 2, this.size, 0);
            ctx.quadraticCurveTo(this.size / 2, this.size / 2, 0, 0);
            ctx.fill();

            ctx.restore();
        }
    }

    for (let i = 0; i < maxPetals; i++) {
        const p = new Petal();
        p.y = Math.random() * height; // Distribuir al inicio
        petals.push(p);
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);
        petals.forEach(petal => {
            petal.update();
            petal.draw();
        });
        requestAnimationFrame(animate);
    }

    animate();
}

// --- MENÚ HAMBURGUESA RESPONSIVE ---
function initMobileMenu() {
    const toggleBtn = document.getElementById('navbar-toggle');
    const menu = document.getElementById('navbar-menu');
    const backdrop = document.getElementById('navbar-backdrop');
    if (!toggleBtn || !menu) return;

    function openMenu() {
        toggleBtn.setAttribute('aria-expanded', 'true');
        menu.classList.add('is-open');
        if (backdrop) backdrop.classList.add('is-visible');
        document.body.classList.add('menu-open');
    }

    function closeMenu() {
        toggleBtn.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
        if (backdrop) backdrop.classList.remove('is-visible');
        document.body.classList.remove('menu-open');
    }

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = toggleBtn.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Cerrar al hacer clic en cualquier enlace del menú móvil
    const links = menu.querySelectorAll('.navbar__link');
    links.forEach(link => {
        link.addEventListener('click', () => {
            closeMenu();
        });
    });

    // Cerrar al hacer clic en el backdrop oscurecido
    if (backdrop) {
        backdrop.addEventListener('click', () => {
            closeMenu();
        });
    }

    // Cerrar con la tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && toggleBtn.getAttribute('aria-expanded') === 'true') {
            closeMenu();
        }
    });

    // Cerrar automáticamente si la ventana se amplía a pantalla de escritorio
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && toggleBtn.getAttribute('aria-expanded') === 'true') {
            closeMenu();
        }
    });
}

// Inicialización general al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    initClipboardCopy();
    initPetalsCanvas();
    initMobileMenu();
});
