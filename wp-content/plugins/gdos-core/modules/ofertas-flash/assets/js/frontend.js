/* REFACTORIZADO: 2025-05-21 */
/* /modules/ofertas-flash/assets/js/frontend.js */

(function() {
    'use strict';

    function initFlashTimers() {
        // 1. Selector múltiple para soportar varios shortcodes en la misma página
        var timers = document.querySelectorAll('.gdos-flash-global-timer');

        if (!timers.length) return;

        timers.forEach(function(root) {
            // Evitar doble inicialización si el JS se carga dos veces (ej: AJAX filtering)
            if (root.getAttribute('data-init') === 'true') return;
            root.setAttribute('data-init', 'true');

            var deadlineRaw = root.getAttribute('data-deadline');
            if (!deadlineRaw) return;

            // PHP envía timestamp en segundos, JS necesita milisegundos
            var deadline = parseInt(deadlineRaw, 10) * 1000;
            var target = root.querySelector('.gdos-count');

            // Función de actualización
            function tick() {
                var now = Date.now();
                var diff = deadline - now;

                // Caso: Tiempo expirado
                if (diff <= 0) {
                    if (target) target.textContent = "00:00:00";
                    
                    // Solo recargar si NO estamos en un entorno de edición/preview
                    var isPreview = window.location.search.indexOf('preview=true') !== -1;
                    var isElementor = document.body.classList.contains('elementor-editor-active');
                    var isCustomizer = typeof wp !== 'undefined' && wp.customize;

                    if (!isPreview && !isElementor && !isCustomizer) {
                        window.location.reload();
                    }
                    return; // Detener ejecución
                }

                // Cálculos matemáticos simples (más rápido que librerías de fecha)
                var totalSeconds = Math.floor(diff / 1000);
                var h = Math.floor(totalSeconds / 3600);
                var m = Math.floor((totalSeconds % 3600) / 60);
                var s = totalSeconds % 60;

                // Formateo con ceros a la izquierda
                // Usamos un array y map para evitar repetición de lógica
                var text = [h, m, s].map(function(val) {
                    return val < 10 ? '0' + val : val;
                }).join(':');

                if (target) target.textContent = text;
            }

            // 2. Ejecutar inmediatamente para evitar ver "--:--:--" por 1 segundo
            tick();

            // 3. Iniciar intervalo
            setInterval(tick, 1000);
        });
    }

    // Inicialización segura (compatible con 'defer' o carga asíncrona)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlashTimers);
    } else {
        initFlashTimers();
    }

})();