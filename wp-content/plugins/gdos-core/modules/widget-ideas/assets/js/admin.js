/* REFACTORIZADO: 2025-05-21 */
/* /modules/widget-ideas/assets/js/admin.js */

jQuery(function($) {
    'use strict';

    // 1. Configuración y Selectores
    const config = window.gdosIdeasWidget || {};
    const $wrapper = $('#gdos-notas-wrapper');
    
    // Si no existe el wrapper (no estamos en el dashboard), salir.
    if (!$wrapper.length) return;

    const $list     = $('#gdos-lista-notas');
    const $textarea = $('#gdos-nota-texto');
    const $addBtn   = $('#gdos-agregar-nota');
    const $status   = $('#gdos-status-msg');

    // 2. Estado Inicial (Precargado desde PHP para WPO)
    // Ya no hacemos AJAX al cargar la página.
    let notesState = Array.isArray(config.notes) ? config.notes : [];

    /**
     * Renderiza la lista basada en el estado actual (notesState).
     * Es una operación puramente local y rápida.
     */
    function render() {
        $list.empty();

        if (notesState.length === 0) {
            $list.append(`<li class="gdos-empty-state" style="border:none; background:transparent; padding:10px; color:#646970; text-align:center;">${config.i18n.empty || 'No hay ideas pendientes.'}</li>`);
            return;
        }

        notesState.forEach(function(noteText, index) {
            // Construcción segura del DOM
            const $li = $('<li/>');
            const $label = $('<label/>');
            const $checkbox = $('<input type="checkbox">');
            const $textSpan = $('<span/>').text(noteText);

            // Evento: Completar tarea
            $checkbox.on('change', function() {
                if (this.checked) {
                    // 1. Feedback visual inmediato (CSS .is-done)
                    $li.addClass('is-done');
                    
                    // 2. Esperar animación y eliminar
                    setTimeout(function() {
                        removeNote(index);
                    }, 500); 
                }
            });

            $label.append($checkbox).append($textSpan);
            $li.append($label);
            $list.append($li);
        });
    }

    /**
     * Agrega una nueva nota al estado y guarda.
     */
    function addNote() {
        const text = $textarea.val().trim();
        if (!text) return;

        // Soporte para pegar listas (dividir por salto de línea)
        const lines = text.split('\n').map(t => t.trim()).filter(t => t.length > 0);

        if (lines.length > 0) {
            // Actualizamos estado local
            notesState = [...notesState, ...lines];
            
            // Limpiamos UI
            $textarea.val('').focus();
            
            // Renderizamos y guardamos
            render();
            saveNotes();
        }
    }

    /**
     * Elimina una nota del estado y guarda.
     */
    function removeNote(indexToRemove) {
        // Filtrar el array eliminando el índice específico
        notesState = notesState.filter((_, index) => index !== indexToRemove);
        render();
        saveNotes();
    }

    /**
     * Sincroniza el estado actual con la Base de Datos vía AJAX.
     * Utiliza Debounce para evitar saturación si se borran muchas rápido.
     */
    let saveTimeout;
    function saveNotes() {
        $status.text(config.i18n.loading).css('color', '#666');
        $list.addClass('is-loading');

        clearTimeout(saveTimeout);
        
        saveTimeout = setTimeout(function() {
            $.post(config.ajax_url, {
                action: 'gdos_guardar_notas',
                nonce: config.nonce,
                notas: JSON.stringify(notesState)
            })
            .done(function(response) {
                if (response.success) {
                    $status.text(config.i18n.saved).css('color', '#00a32a');
                    // Borrar mensaje de "Guardado" después de 3 segundos
                    setTimeout(() => $status.fadeOut(function(){ $(this).text('').show(); }), 3000);
                } else {
                    $status.text(config.i18n.error).css('color', '#d63638');
                }
            })
            .fail(function() {
                $status.text('Error de red').css('color', '#d63638');
            })
            .always(function() {
                $list.removeClass('is-loading');
            });
        }, 500); // Espera 500ms desde la última acción para guardar
    }

    // 3. Event Listeners
    $addBtn.on('click', addNote);

    // Permitir guardar con Enter (sin Shift) en el textarea
    $textarea.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            addNote();
        }
    });

    // 4. Renderizado Inicial Instantáneo
    render();
});