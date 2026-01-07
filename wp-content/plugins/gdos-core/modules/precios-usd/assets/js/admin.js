/* REFACTORIZADO: 2025-05-21 */
/* /modules/precios-usd/assets/js/admin.js */

jQuery(function($) {
    'use strict';

    // 1. Cachear elementos del DOM para mejorar rendimiento
    const $currencySelect = $('#_moneda_producto');
    const $usdFields      = $('.gdos-usd-field');

    // Fail-fast: Si el campo no existe en esta página, no hacemos nada.
    if (!$currencySelect.length) return;

    /**
     * Alterna la visibilidad de los campos USD.
     * @param {boolean} animate - Si debe usar animación (true) o ser instantáneo (false).
     */
    function toggleUSDFields(animate = true) {
        const isUSD = $currencySelect.val() === 'usd';

        if (isUSD) {
            animate ? $usdFields.stop(true, true).slideDown(200) : $usdFields.show();
        } else {
            animate ? $usdFields.stop(true, true).slideUp(200) : $usdFields.hide();
        }
    }

    // 2. Estado Inicial (Sin animación para evitar layout shift al cargar)
    toggleUSDFields(false);

    // 3. Event Listener (Con animación para mejor UX)
    $currencySelect.on('change', function() {
        toggleUSDFields(true);
    });
});