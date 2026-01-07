/* REFACTORIZADO: 2025-05-21 (Fix: Eliminar style inline) */
/* /modules/modal-opciones-pago/assets/js/frontend.js */

(function() {
    'use strict';

    // Variable para accesibilidad (guardar foco)
    let lastFocusedElement;

    function toggleBodyLock(isLocked) {
        document.body.style.overflow = isLocked ? 'hidden' : '';
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
        
        // Esperamos la transición CSS (0.3s) y luego volvemos a ocultar
        setTimeout(() => {
            modal.style.display = 'none'; // Restaurar display:none
            toggleBodyLock(false);
        }, 300);

        if (lastFocusedElement) {
            lastFocusedElement.focus();
            lastFocusedElement = null;
        }
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        lastFocusedElement = document.activeElement;

        // --- LA SOLUCIÓN MÁGICA ---
        // Forzamos el display a flex directamente en el elemento
        // esto sobreescribe el style="display:none" del PHP
        modal.style.display = 'flex';
        
        // Pequeño delay para permitir que el navegador procese el display:flex
        // antes de añadir la clase de opacidad, permitiendo la animación.
        requestAnimationFrame(() => {
            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');
        });

        toggleBodyLock(true);

        const closeBtn = modal.querySelector('.gdos-modal-close');
        if (closeBtn) {
            setTimeout(() => closeBtn.focus(), 50);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Delegación de eventos para clicks
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-gdos-modal-trigger]');
            
            // ABRIR
            if (trigger) {
                e.preventDefault(); 
                const rawId = trigger.getAttribute('data-gdos-modal-trigger');
                const finalId = 'gdos-modal-' + rawId;
                openModal(finalId);
                return;
            }

            // CERRAR (Botón X o Cerrar)
            const closeBtn = e.target.closest('[data-gdos-modal-close]');
            if (closeBtn) {
                e.preventDefault();
                closeModal(closeBtn.closest('.gdos-modal-backdrop'));
                return;
            }

            // CERRAR (Click fuera)
            if (e.target.classList.contains('gdos-modal-backdrop')) {
                closeModal(e.target);
            }
        });

        // Tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModalEl = document.querySelector('.gdos-modal-backdrop.is-visible');
                if (openModalEl) closeModal(openModalEl);
            }
        });
    });
})();