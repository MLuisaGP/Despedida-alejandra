/**
 * Guestbook Module (Libro de Firmas)
 * 
 * Responsabilidad única: Gestionar el envío asíncrono (AJAX / Fetch) del formulario
 * de firmas y actualizar visualmente el muro de recuerdos en tiempo real.
 */

document.addEventListener('DOMContentLoaded', () => {
    initGuestbook();
});

function initGuestbook() {
    const form = document.getElementById('guestbook-form');
    const wall = document.getElementById('guestbook-wall');
    const emptyNotice = document.getElementById('guestbook-empty');
    const submitBtn = document.getElementById('guestbook-submit-btn');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const authorInput = document.getElementById('guestbook-author');
        const messageInput = document.getElementById('guestbook-message');

        const author = authorInput ? authorInput.value.trim() : '';
        const message = messageInput ? messageInput.value.trim() : '';

        // Validación en cliente
        if (!author) {
            window.showToast?.('Por favor ingresa tu nombre en "De parte de".', 'error');
            authorInput?.focus();
            return;
        }

        if (!message) {
            window.showToast?.('Por favor escribe un deseo, anécdota o recuerdo.', 'error');
            messageInput?.focus();
            return;
        }

        // Estado de carga en el botón
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : 'Firmar Libro';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Guardando con amor...</span>';
        }

        try {
            const payload = { author, message };

            // Intentar primero con la ruta estándar /api/guestbook
            let endpoint = (window.API_BASE_URL || '') + '/api/guestbook';
            let response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            // Si el servidor devolvió HTML (ej. mod_rewrite no activo), recurrir a api.php directamente
            let contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const fallbackEndpoint = (window.API_BASE_URL || '') + '/api.php?action=guestbook';
                response = await fetch(fallbackEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                contentType = response.headers.get('content-type') || '';
            }

            if (!contentType.includes('application/json')) {
                throw new Error('El servidor no respondió en formato JSON. Por favor recarga la página e intenta de nuevo.');
            }

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'No fue posible guardar la firma.');
            }

            // Éxito: Notificar al usuario
            window.showToast?.(result.message || '¡Tu recuerdo ha sido firmado con éxito!', 'success');

            // Resetear formulario
            form.reset();

            // Insertar tarjeta al principio del muro
            if (wall && result.data) {
                if (emptyNotice) {
                    emptyNotice.style.display = 'none';
                }

                const cardHtml = createGuestbookCardHtml(result.data);
                wall.insertAdjacentHTML('afterbegin', cardHtml);

                // Animación de entrada suave
                const firstCard = wall.firstElementChild;
                if (firstCard) {
                    firstCard.style.opacity = '0';
                    firstCard.style.transform = 'translateY(-15px)';
                    setTimeout(() => {
                        firstCard.style.transition = 'all 0.4s ease';
                        firstCard.style.opacity = '1';
                        firstCard.style.transform = 'translateY(0)';
                    }, 50);
                }
            }

        } catch (error) {
            console.error('Error en libro de firmas:', error);
            window.showToast?.(error.message || 'Ocurrió un error al enviar tu mensaje.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        }
    });
}

/**
 * Genera el HTML de una tarjeta de recuerdo
 */
function createGuestbookCardHtml(item) {
    const safeAuthor = escapeHtml(item.author || 'Invitado');
    const safeMessage = escapeHtml(item.message || '');
    const dateStr = item.formatted_date || 'Hace un momento';

    return `
        <article class="guestbook-section__entry">
            <div class="guestbook-section__entry-header">
                <h4 class="guestbook-section__entry-author">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    ${safeAuthor}
                </h4>
                <time class="guestbook-section__entry-date">${dateStr}</time>
            </div>
            <p class="guestbook-section__entry-message">"${safeMessage}"</p>
        </article>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
