/* REFACTORIZADO: 2025-05-21 */
/* /modules/modal-widget/assets/js/modal.js */

(function() {
    'use strict';

    /**
     * Intenta copiar texto al portapapeles usando la API moderna,
     * con fallback a método legacy para navegadores antiguos o HTTP.
     * @param {string} text
     * @returns {Promise<boolean>}
     */
    async function copyToClipboard(text) {
        if (!text) return false;

        // 1. API Moderna (Requiere HTTPS)
        if (navigator.clipboard && navigator.clipboard.writeText) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                // Si falla (ej: permisos), intentamos fallback
            }
        }

        // 2. Fallback (Textarea temporal)
        return new Promise((resolve) => {
            try {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                
                // Asegurar que no sea visible ni cause scroll
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                textArea.style.top = "0";
                
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                resolve(successful);
            } catch (err) {
                resolve(false);
            }
        });
    }

    // Event Delegation (Mejor rendimiento que añadir listeners a cada botón)
    document.addEventListener('click', function(e) {
        // Buscar el botón más cercano al clic
        const btn = e.target.closest('.gdos-coupon__btn');
        
        // Validaciones iniciales
        if (!btn || btn.classList.contains('copied')) return;

        e.preventDefault();

        const wrapper = btn.closest('.gdos-coupon');
        if (!wrapper) return;

        // Buscar el elemento que contiene el código
        const codeEl = wrapper.querySelector('.gdos-coupon__hidden-code');
        if (!codeEl) return;

        // Obtener el código: Soporta INPUT (.value) o SPAN/DIV (.textContent)
        const codeText = (codeEl.value || codeEl.textContent || '').trim();

        if (!codeText) return;

        // Ejecutar Copiado
        copyToClipboard(codeText).then(function(success) {
            if (success) {
                // --- FEEDBACK VISUAL ---
                
                // 1. Guardar estado original
                const originalHtml = btn.innerHTML;
                // Fijar ancho para evitar "saltos" visuales al cambiar texto
                const originalWidth = btn.getBoundingClientRect().width;
                btn.style.width = originalWidth + 'px';
                btn.style.justifyContent = 'center'; // Asegurar centrado

                // 2. Cambiar a estado "Copiado"
                btn.classList.add('copied');
                // Reemplazamos contenido temporalmente
                btn.innerHTML = '<span class="gdos-coupon__btn-icon"><i class="fas fa-check"></i></span><span>¡LISTO!</span>';

                // 3. Restaurar después de 2 segundos
                setTimeout(function() {
                    btn.classList.remove('copied');
                    btn.innerHTML = originalHtml;
                    btn.style.width = ''; // Liberar ancho
                    btn.style.justifyContent = '';
                }, 2000);

            } else {
                alert('No se pudo copiar automáticamente. Código: ' + codeText);
            }
        });
    });

})();