/* REFACTORIZADO: 2025-05-21 */
// /modules/manual-producto/assets/js/admin.js

jQuery(function($){
    'use strict';

    let mediaFrame;
    
    // Cachear Selectores
    const $input    = $('#gdos_manual_pdf_url');
    const $clearBtn = $('#gdos_manual_pdf_clear');
    const $selectBtn = $('#gdos_manual_pdf_select');

    // --- 1. Abrir Media Uploader (Singleton) ---
    $selectBtn.on('click', function(e){
        e.preventDefault();

        // Reutilizar frame existente (ahorro de memoria)
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        // Crear nueva instancia, filtrando por PDF
        mediaFrame = wp.media({
            title: 'Seleccionar manual (PDF)',
            button: { text: 'Usar este PDF' },
            library: { type: 'application/pdf' }, 
            multiple: false
        });

        mediaFrame.on('select', function(){
            const file = mediaFrame.state().get('selection').first().toJSON();
            if (file && file.url) {
                $input.val(file.url);
                $clearBtn.show();
            }
        }).open();
    });

    // --- 2. Limpiar Campo ---
    $clearBtn.on('click', function(e){
        e.preventDefault();
        $input.val('');
        $clearBtn.hide();
    });
});