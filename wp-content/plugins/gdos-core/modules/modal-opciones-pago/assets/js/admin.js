/* /modules/modal-opciones-pago/assets/js/admin.js */

jQuery(function($) {
    'use strict';

    const $container = $('#gdos-cards-list');
    
    // Función centralizada para re-calcular índices
    function updateIndices() {
        $container.find('.gdos-card-row').each(function(index) {
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    // Reemplaza cards[XXX][campo] por cards[INDEX][campo]
                    // Regex robusta que acepta números o __INDEX__
                    const newName = name.replace(/cards\[[^\]]+\]/, `cards[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // 1. Sortable (Arrastrar)
    if ($.fn.sortable) {
        $container.sortable({
            handle: '.gdos-card-handle',
            axis: 'y',
            placeholder: 'ui-sortable-placeholder', // Clase definida en CSS
            update: function() {
                updateIndices();
            }
        });
    }

    // 2. Añadir Tarjeta
    $('#gdos-add-card').on('click', function(e) {
        e.preventDefault();
        const template = wp.template('gdos-card-row');
        // Agregamos al final
        $container.append(template());
        // Corregimos índices inmediatamente para que el nuevo tenga el ID correcto
        updateIndices();
    });

    // 3. Borrar Tarjeta
    $container.on('click', '.gdos-remove-row', function(e) {
        e.preventDefault();
        if (confirm('¿Eliminar esta tarjeta?')) {
            $(this).closest('.gdos-card-row').remove();
            updateIndices();
        }
    });

    // 4. Media Uploader (Singleton)
    let mediaFrame;
    let $currentBtn;

    $('#gdos-cards-list').on('click', '.gdos-upload-img', function(e) {
        e.preventDefault();
        $currentBtn = $(this);

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: 'Seleccionar Logo',
            library: { type: 'image' },
            button: { text: 'Usar Logo' },
            multiple: false
        });

        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            const $row = $currentBtn.closest('.gdos-card-row');
            
            // Guardar ID
            $row.find('.img-id-input').val(attachment.id);
            // Mostrar Preview
            $row.find('.gdos-card-img-preview').html(`<img src="${attachment.url}" alt="">`);
        });

        mediaFrame.open();
    });
});