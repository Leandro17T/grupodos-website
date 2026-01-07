/**
 * REFACTORIZADO: 2025-12-07 (Soporte Cruce de Medianoche)
 * Módulo: Banner Principal (Admin)
 * Descripción: Gestión de filas repetibles, ordenamiento y media uploader.
 */
(function ($) {
    'use strict';

    const mainCard = $('.gdos-card');
    if (!mainCard.length) return;

    // --- CONFIGURACIÓN ---
    const SIZES = {
        desktop: 'ESCRITORIO: 1440 × 400',
        tablet:  'TABLET: 1024 × 768',
        mobile:  'MÓVIL: 600 × 640'
    };

    /**
     * Sincroniza la cabecera (pestaña activa) con los botones de acción.
     */
    function syncHeaderActions(type) {
        const actions = mainCard.find('.gdos-actions');
        
        // Guardamos el tipo actual en los botones para saber qué agregar
        actions.find('.gdos-add-multiple, .gdos-add-one').data('type', type);
        
        // Actualizamos la píldora de tamaño recomendado
        const label = SIZES[type] || SIZES.desktop;
        actions.find('[data-size-pill]').html(
            `<span class="dashicons dashicons-info"></span> Tamaño recomendado → <strong>${label}</strong>`
        );
    }

    // --- EVENTOS DE PESTAÑAS ---
    mainCard.on('click', '.gdos-tab', function () {
        const tab = $(this);
        
        // UI Tabs
        mainCard.find('.gdos-tab').removeClass('active');
        tab.addClass('active');
        
        // UI Panels
        mainCard.find('.gdos-panel').removeClass('active');
        $(tab.data('target')).addClass('active');
        
        // Sincronizar contexto
        syncHeaderActions(tab.data('type'));
    });

    // --- GESTIÓN DE FILAS (CRUD) ---

    /**
     * Agrega una nueva fila al panel activo.
     * @param {string} type - desktop, tablet, mobile
     * @param {object|null} attachment - Objeto JSON de WP Media (opcional)
     */
    function addRow(type, attachment) {
        const container = mainCard.find('#gdos-panel-' + type + ' .gdos-list');
        const template  = $('#gdos-row-template').html();
        
        // Reemplazo simple del placeholder __TYPE__
        const html = template.replace(/__TYPE__/g, type);
        const newRow = $(html);

        // Si viene con imagen (desde selección múltiple), la pre-cargamos
        if (attachment) {
            newRow.find('[data-id]').val(attachment.id);
            
            // Preferir thumbnail, fallback a full url
            const thumbUrl = (attachment.sizes && attachment.sizes.thumbnail) 
                ? attachment.sizes.thumbnail.url 
                : attachment.url;
                
            newRow.find('[data-thumb]').html($('<img>', { src: thumbUrl }));
        }

        container.append(newRow);
        initRow(newRow);
    }

    // Botón: Agregar Vacío
    mainCard.on('click', '.gdos-add-one', function () {
        addRow($(this).data('type'));
    });

    // Botón: Agregar Múltiples (Media Uploader)
    mainCard.on('click', '.gdos-add-multiple', function () {
        const type = $(this).data('type');
        
        const frame = wp.media({
            title: 'Seleccionar Imágenes para Banners',
            multiple: true,
            library: { type: 'image' },
            button: { text: 'Agregar al Banner' }
        });

        frame.on('select', () => {
            const selection = frame.state().get('selection');
            selection.each(att => addRow(type, att.toJSON()));
        });

        frame.open();
    });

    // Botón: Seleccionar Imagen Individual (Dentro de la fila)
    mainCard.on('click', '.gdos-pick', function () {
        const row = $(this).closest('.gdos-row');
        
        const frame = wp.media({
            title: 'Cambiar Imagen',
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', () => {
            const att = frame.state().get('selection').first().toJSON();
            row.find('[data-id]').val(att.id);
            
            const thumbUrl = (att.sizes && att.sizes.thumbnail) 
                ? att.sizes.thumbnail.url 
                : att.url;
                
            row.find('[data-thumb]').html($('<img>', { src: thumbUrl }));
        });

        frame.open();
    });

    // Botón: Quitar Imagen
    mainCard.on('click', '.gdos-clear', function () {
        const row = $(this).closest('.gdos-row');
        row.find('[data-id]').val('');
        row.find('[data-thumb]').html('<span class="placeholder">Sin imagen</span>');
    });

    // Botón: Eliminar Fila
    mainCard.on('click', '.gdos-remove', function () {
        if (confirm('¿Eliminar este banner?')) {
            $(this).closest('.gdos-row').fadeOut(300, function() { 
                $(this).remove(); 
            });
        }
    });

    // --- INICIALIZACIÓN DE FILA ---
    function initRow(row) {
        const jqRow = $(row);

        // 1. Sincronización de Días (Checkboxes -> Input Hidden)
        jqRow.find('[data-days-group]').on('change', 'input', function () {
            const group = $(this).closest('[data-days-group]');
            const values = group.find('input:checked').map((i, el) => $(el).val()).get();
            group.next('[data-days-hidden]').val(values.join(','));
        });

        // 2. Flatpickr para Fechas
        jqRow.find('input[type="date"]').each(function () {
            if (!this._flatpickr) {
                flatpickr(this, {
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d M, Y",
                    locale: "es" // Asume que cargaste el locale o usa default
                });
            }
        });

        // 3. Estado Inicial (Chip)
        computeRowStatus(jqRow[0]);
    }

    /**
     * Calcula si el banner está activo, programado o caducado.
     * SOPORTE PARA CRUCE DE MEDIANOCHE
     */
    function computeRowStatus(rowElement) {
        const row = $(rowElement);
        const today = new Date();
        
        // Fecha actual Y-m-d
        const y = today.getFullYear();
        const m = ('0' + (today.getMonth() + 1)).slice(-2);
        const d = ('0' + today.getDate()).slice(-2);
        const todayStr = `${y}-${m}-${d}`;
        
        // Hora actual en minutos
        const nowMin = today.getHours() * 60 + today.getMinutes();

        // Valores de inputs
        const df = row.find('input[name*="_date_from"]').val() || '';
        const dt = row.find('input[name*="_date_to"]').val() || '';
        const tf = row.find('input[name*="_time_from"]').val() || '';
        const tt = row.find('input[name*="_time_to"]').val() || '';

        let status = 'ok'; // Por defecto activo

        // Lógica de Fechas
        if (df && todayStr < df) status = 'warn'; // Futuro
        else if (dt && todayStr > dt) status = 'off';  // Pasado
        
        // Si la fecha es válida, miramos la hora
        else {
            const parseTime = (t) => {
                if (!t) return null;
                const [h, m] = t.split(':').map(Number);
                if (isNaN(h) || isNaN(m)) return null;
                return h * 60 + m;
            };

            const fm = parseTime(tf);
            const tm = parseTime(tt);

            // Valores numéricos seguros (00:00 y 23:59 por defecto)
            const startM = fm !== null ? fm : 0;
            const endM   = tm !== null ? tm : 1439;

            if (startM <= endM) {
                // RANGO NORMAL (Ej: 09:00 a 17:00)
                if (nowMin < startM) status = 'warn';      // Aún no empieza hoy
                else if (nowMin > endM) status = 'off';    // Ya terminó por hoy
            } else {
                // CRUCE DE MEDIANOCHE (Ej: 22:00 a 06:00)
                // Es válido si estamos después del inicio OR antes del fin.
                // Es inválido (warn) solo si estamos en el "hueco" del medio.
                
                if (nowMin > endM && nowMin < startM) {
                    status = 'warn'; // Estamos en el hueco del mediodía
                }
            }
        }

        // Renderizado del Chip
        const labels = { ok: 'ACTIVO', warn: 'PROGRAMADO', off: 'INACTIVO' };
        const classes = { ok: 'gdos-chip--ok', warn: 'gdos-chip--warn', off: 'gdos-chip--off' };
        
        let holder = row.find('.gdos-chip-holder');
        if (!holder.length) {
            holder = $('<div class="gdos-chip-holder"></div>');
            row.find('.gdos-data-col').prepend(holder);
        }
        
        holder.html(`<span class="gdos-chip ${classes[status]}">${labels[status]}</span>`);
    }

    // --- EVENTOS GLOBALES ---
    
    // Recalcular estado al cambiar inputs
    mainCard.on('change', 'input', function (e) {
        const row = $(e.target).closest('.gdos-row');
        if (row.length) computeRowStatus(row[0]);
    });

    // Inicializar Sortable (jQuery UI)
    $('.gdos-list').sortable({
        handle: '.gdos-handle',
        placeholder: 'ui-sortable-placeholder',
        axis: 'y',
        forcePlaceholderSize: true
    });

    // Inicializar filas existentes al cargar
    mainCard.find('.gdos-row').each((i, row) => initRow(row));
    
    // Iniciar en pestaña Desktop
    syncHeaderActions('desktop');

})(jQuery);