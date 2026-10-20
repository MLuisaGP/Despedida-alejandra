/**
 * Countdown Timer Module
 * 
 * Responsabilidad única: Calcular y actualizar en tiempo real el reloj
 * con cuenta regresiva hasta la fecha y hora de la despedida de solteros.
 */

document.addEventListener('DOMContentLoaded', () => {
    initCountdown();
});

function initCountdown() {
    // Contenedores del DOM para los dígitos
    const daysEl = document.getElementById('cd-days');
    const hoursEl = document.getElementById('cd-hours');
    const minutesEl = document.getElementById('cd-minutes');
    const secondsEl = document.getElementById('cd-seconds');
    const statusEl = document.getElementById('cd-status');

    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) {
        return;
    }

    // Fecha del evento: 29 de Octubre de 2026 a las 17:00:00 (5:00 PM)
    // Puede ser leída de un atributo data en el HTML si existe
    const countdownContainer = document.querySelector('[data-target-date]');
    const targetDateStr = countdownContainer 
        ? countdownContainer.getAttribute('data-target-date') 
        : '2026-10-29T17:00:00';

    const targetDate = new Date(targetDateStr).getTime();

    // Función de actualización por segundo
    function updateCountdown() {
        const now = new Date().getTime();
        const difference = targetDate - now;

        if (difference <= 0) {
            // El evento ya comenzó o llegó a su fecha
            daysEl.textContent = '00';
            hoursEl.textContent = '00';
            minutesEl.textContent = '00';
            secondsEl.textContent = '00';
            if (statusEl) {
                statusEl.textContent = '¡El gran día ha llegado! Bienvenidos a nuestra despedida.';
            }
            clearInterval(intervalId);
            return;
        }

        // Cálculos matemáticos de tiempo
        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        // Formato con ceros a la izquierda (01, 02, etc.)
        daysEl.textContent = String(days).padStart(2, '0');
        hoursEl.textContent = String(hours).padStart(2, '0');
        minutesEl.textContent = String(minutes).padStart(2, '0');
        secondsEl.textContent = String(seconds).padStart(2, '0');
    }

    // Ejecutar inmediatamente y luego cada 1 segundo
    updateCountdown();
    const intervalId = setInterval(updateCountdown, 1000);
}
