/* REFACTORIZADO: 2025-05-21 (Con WooCommerce Select2) */
/* /modules/ofertas-flash/assets/js/admin.js */

jQuery(function($) {
    'use strict';

    const $root = $('#gdos-flash-admin-root');
    if (!$root.length) return;

    // 1. MEDIA MANAGER (Singleton)
    let mediaFrame;
    let $currentMediaButton;

    $root.on('click', '.gdos-upload-media-button', function(e) {
        e.preventDefault();
        $currentMediaButton = $(this);
        if (mediaFrame) { mediaFrame.open(); return; }
        mediaFrame = wp.media({ title: 'Seleccionar GIF o SVG', library: { type: 'image' }, button: { text: 'Usar' }, multiple: false });
        mediaFrame.on('select', function() {
            const att = mediaFrame.state().get('selection').first().toJSON();
            const $w = $currentMediaButton.closest('.gdos-image-uploader');
            $w.find('input[name$="[title_media_id]"]').val(att.id);
            $w.find('input[name$="[title_media_type]"]').val(att.mime === 'image/gif' ? 'gif' : 'svg');
            $w.find('.gdos-image-preview').html(`<img src="${att.url}" style="max-width:60px;">`);
            $w.find('.gdos-remove-media-button').show();
        });
        mediaFrame.open();
    });

    $root.on('click', '.gdos-remove-media-button', function(e) {
        e.preventDefault();
        const $w = $(this).closest('.gdos-image-uploader');
        $w.find('input[type="hidden"]').val('');
        $w.find('.gdos-image-preview').empty();
        $(this).hide();
    });

    // 2. PLANIFICADOR DE DÍAS
    const $daysContainer = $('#gdos-days-container');

    if ($.fn.sortable) {
        $daysContainer.sortable({
            handle: '.day-handle', placeholder: 'day-card-placeholder', axis: 'y',
            update: function() { updateDayIndices(); }
        });
    }

    // Inicializar Select2 en carga inicial
    $(document.body).trigger('wc-enhanced-select-init');

    // Añadir Día
    $('#gdos-add-day').on('click', function(e) {
        e.preventDefault();
        const template = wp.template('gdos-day-card');
        // Agregamos al DOM
        const $newDay = $(template()).appendTo($daysContainer);
        
        updateDayIndices();
        
        // Inicializar el select2 del slot vacío que viene por defecto
        $(document.body).trigger('wc-enhanced-select-init');
    });

    // Eliminar Día
    $daysContainer.on('click', '.day-remove', function(e) {
        e.preventDefault();
        if (confirm('¿Eliminar día?')) {
            $(this).closest('.day-card').remove();
            updateDayIndices();
        }
    });

    function updateDayIndices() {
        $daysContainer.find('.day-card').each(function(index) {
            const $card = $(this);
            $card.attr('data-day-index', index);
            $card.find('.day-title .js-day-number').text(index + 1);
            
            // Reemplazo robusto para Selects e Inputs
            $card.find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[days\]\[[^\]]+\]/, `[days][${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // 3. GESTIÓN DE PRODUCTOS (Select2 AJAX)
    $daysContainer.on('click', '.sku-add-button', function(e) {
        e.preventDefault();
        const $dayCard = $(this).closest('.day-card');
        const dayIndex = $dayCard.attr('data-day-index');
        const $productsList = $dayCard.find('.day-products');
        
        const template = wp.template('gdos-sku-slot');
        let html = template({}); 
        html = html.replace(/__DAY_INDEX__/g, dayIndex);
        
        // Insertamos el nuevo slot
        const $newSlot = $(html).appendTo($productsList);
        
        // ¡MAGIA! Despertamos el buscador de WooCommerce
        $(document.body).trigger('wc-enhanced-select-init');
        
        // Intentar abrirlo automáticamente para mejor UX (opcional)
        try { $newSlot.find('select').select2('open'); } catch(e){}
    });

    $daysContainer.on('click', '.sku-remove-button', function(e) {
        e.preventDefault();
        $(this).closest('.sku-slot').remove();
    });
    
    // Ejecución inicial de índices
    updateDayIndices();
});