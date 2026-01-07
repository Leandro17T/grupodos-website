/**
 * REFACTORIZADO: 2025-12-06
 * Módulo: Fecha Entrega Estimada
 * Descripción: Calcula fechas de entrega hábiles (L-V) dinámicamente.
 */
(function () {
    'use strict';

    // Configuración
    const CONFIG = {
        selector: '.gdos-fecha-entrega',
        locale: 'es-ES',
        textPrefix: 'Comprando desde el interior del país entrega estimada: ',
        separator: ' - '
    };

    /**
     * Función principal de inicialización
     */
    const init = () => {
        const containers = document.querySelectorAll(CONFIG.selector);
        if (!containers.length) return;

        try {
            const range = calculateDeliveryRange();
            render(containers, range);
        } catch (error) {
            console.error('GDOS Delivery Error:', error);
        }
    };

    /**
     * Renderiza el texto en el DOM
     * @param {NodeList} containers 
     * @param {Object} range { start: string, end: string }
     */
    const render = (containers, range) => {
        // Pre-construimos el HTML una sola vez
        const html = `${CONFIG.textPrefix}<b>${range.start}</b>${CONFIG.separator}<b>${range.end}</b>`;
        
        containers.forEach(el => {
            el.innerHTML = html;
        });
    };

    /**
     * Calcula el rango de fechas de entrega basado en días hábiles.
     * Lógica: 
     * - Inicio: Próximo día hábil (Mañana, o Lunes si es Vie/Sab/Dom).
     * - Fin: El día hábil siguiente al de inicio.
     */
    const calculateDeliveryRange = () => {
        const today = new Date();
        
        // 1. Calcular Fecha de Inicio (Próximo día hábil desde hoy)
        // Si hoy es Viernes, el próximo hábil es Lunes (+3). Lunes-Jueves es +1.
        let startDate = addBusinessDays(today, 1);

        // 2. Calcular Fecha Fin (1 día hábil después de la fecha de inicio)
        let endDate = addBusinessDays(startDate, 1);

        return {
            start: formatDate(startDate),
            end: formatDate(endDate)
        };
    };

    /**
     * Añade días hábiles a una fecha, saltando fines de semana.
     * @param {Date} date Fecha base
     * @param {number} days Días a sumar
     * @returns {Date} Nueva fecha clonada
     */
    const addBusinessDays = (date, days) => {
        let result = new Date(date);
        let added = 0;
        
        while (added < days) {
            result.setDate(result.getDate() + 1);
            const day = result.getDay();
            // 0 = Domingo, 6 = Sábado. Si no es finde, sumamos al contador.
            if (day !== 0 && day !== 6) {
                added++;
            }
        }
        return result;
    };

    /**
     * Formatea fecha usando Intl API (Más rápido y estándar)
     * @param {Date} date 
     * @returns {string} Ej: "6 de diciembre"
     */
    const formatDate = (date) => {
        return new Intl.DateTimeFormat(CONFIG.locale, {
            day: 'numeric',
            month: 'long'
        }).format(date);
    };

    // Ejecución segura (soporta async/defer y DOMContentLoaded estándar)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();