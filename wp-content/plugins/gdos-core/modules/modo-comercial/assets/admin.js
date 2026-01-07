/* REFACTORIZADO: 2025-05-21 */
/* /modules/modo-comercial/assets/js/admin.js */

jQuery(function($){
    'use strict';

    // UX: Opacar la configuración si el interruptor principal está apagado
    const $toggle   = $('input[name*="[enabled]"]');
    // Seleccionamos todo excepto la primera fila (donde está el switch)
    const $settings = $('.gdos-mc-grid, .gdos-mc-row-col, .gdos-mc-row:not(:first-child)');

    function updateUI() {
        if ($toggle.is(':checked')) {
            $settings.stop().animate({ opacity: 1 }, 200).css('pointer-events', 'auto');
        } else {
            $settings.stop().animate({ opacity: 0.5 }, 200).css('pointer-events', 'none');
        }
    }

    if ($toggle.length) {
        $toggle.on('change', updateUI);
        // Ejecutar al cargar para establecer el estado inicial
        updateUI();
    }
});