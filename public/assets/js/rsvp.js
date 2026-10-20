/**
 * RSVP Module (Confirmación de Asistencia)
 * 
 * Responsabilidad única: Validar y procesar el formulario de confirmación de asistencia
 * mediante peticiones asíncronas hacia el backend en PHP / MySQL.
 */

document.addEventListener('DOMContentLoaded', () => {
    initRsvp();
});

function initRsvp() {
    const form = document.getElementById('rsvp-form');
    const submitBtn = document.getElementById('rsvp-submit-btn');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const firstNameInput = document.getElementById('rsvp-first-name');
        const lastNameInput = document.getElementById('rsvp-last-name');
        const statusSelect = document.getElementById('rsvp-status');
        const phoneInput = document.getElementById('rsvp-phone');

        const firstName = firstNameInput ? firstNameInput.value.trim() : '';
        const lastName = lastNameInput ? lastNameInput.value.trim() : '';
        const attendance_status = statusSelect ? statusSelect.value : 'confirmed';
        const phone = phoneInput ? phoneInput.value.trim() : '';

        // Validaciones en cliente
        if (!firstName) {
            window.showToast?.('Por favor ingresa tu nombre.', 'error');
            firstNameInput?.focus();
            return;
        }

        if (!lastName) {
            window.showToast?.('Por favor ingresa tus apellidos.', 'error');
            lastNameInput?.focus();
            return;
        }

        // Estado visual de carga
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : 'Confirmar Asistencia';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Registrando tu respuesta...</span>';
        }

        try {
            const payload = {
                first_name: firstName,
                last_name: lastName,
                attendance_status,
                phone
            };

            // Intentar primero con la ruta estándar /api/rsvp
            let endpoint = (window.API_BASE_URL || '') + '/api/rsvp';
            let response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            // Si el servidor devolvió HTML (ej. falta mod_rewrite en Apache o respuesta index.php), recurrir a api.php directamente
            let contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const fallbackEndpoint = (window.API_BASE_URL || '') + '/api.php?action=rsvp';
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
                if (result.capacity_full) {
                    const banner = document.getElementById('rsvp-capacity-banner');
                    if (banner) {
                        banner.style.display = 'block';
                    }
                    form.style.display = 'none';
                }
                throw new Error(result.message || 'Error al procesar tu confirmación.');
            }

            // Notificación flotante
            window.showToast?.(result.message, 'success');

            // Ocultar el formulario para remover los inputs de la vista
            form.style.display = 'none';

            // Preparar y mostrar el contenedor visual de confirmación
            const successState = document.getElementById('rsvp-success-state');
            const iconBox = document.getElementById('rsvp-success-icon-box');
            const iconCheck = document.getElementById('rsvp-success-icon-check');
            const iconHeart = document.getElementById('rsvp-success-icon-heart');
            const badge = document.getElementById('rsvp-success-badge');
            const title = document.getElementById('rsvp-success-title');
            const text = document.getElementById('rsvp-success-text');

            const isAttending = (attendance_status === 'confirmed');

            if (successState) {
                if (isAttending) {
                    if (iconBox) {
                        iconBox.style.background = 'rgba(42, 157, 143, 0.14)';
                        iconBox.style.borderColor = '#2A9D8F';
                        iconBox.style.color = '#2A9D8F';
                    }
                    if (iconCheck) iconCheck.style.display = 'block';
                    if (iconHeart) iconHeart.style.display = 'none';
                    if (badge) {
                        badge.textContent = '¡Lugar Reservado con Éxito!';
                        badge.style.color = '#2A9D8F';
                    }
                    if (title) title.textContent = '¡Gracias por confirmar tu asistencia!';
                } else {
                    if (iconBox) {
                        iconBox.style.background = 'rgba(194, 123, 127, 0.14)';
                        iconBox.style.borderColor = '#C27B7F';
                        iconBox.style.color = '#C27B7F';
                    }
                    if (iconCheck) iconCheck.style.display = 'none';
                    if (iconHeart) iconHeart.style.display = 'block';
                    if (badge) {
                        badge.textContent = 'Respuesta Registrada';
                        badge.style.color = '#C27B7F';
                    }
                    if (title) title.textContent = 'Lamentamos que no puedas ir';
                }

                if (text) {
                    text.textContent = result.message;
                }

                successState.style.display = 'block';

                // Desplazamiento suave para visibilidad inmediata
                successState.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Si tras este registro se cubrió la totalidad del cupo
            if (result.is_full) {
                const banner = document.getElementById('rsvp-capacity-banner');
                if (banner) {
                    banner.style.display = 'block';
                }
            }

        } catch (error) {
            console.error('Error en RSVP:', error);
            window.showToast?.(error.message || 'Ocurrió un error al procesar tu confirmación.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        }
    });

    // Botón para registrar otra asistencia (vuelve a mostrar los inputs)
    const resetBtn = document.getElementById('rsvp-reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            const successState = document.getElementById('rsvp-success-state');
            if (successState) {
                successState.style.display = 'none';
            }

            form.reset();
            form.style.display = 'block';

            const firstNameInput = document.getElementById('rsvp-first-name');
            firstNameInput?.focus();

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }
}
