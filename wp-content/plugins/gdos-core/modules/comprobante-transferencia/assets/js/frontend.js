/**
 * REFACTORIZADO: 2025-12-06
 * Módulo: Comprobante de Transferencia
 * Descripción: Maneja Dropzone y reestructura visualmente los datos bancarios.
 */
(function () {
    'use strict';

    // 1. Manejo del Dropzone de Archivos
    const initDropzone = () => {
        const dz = document.getElementById('gdos-dropzone');
        const input = document.getElementById('gdos_slip');
        const fileNameDisplay = document.getElementById('gdos-file-name');

        if (!dz || !input) return;

        // Click Trigger
        dz.addEventListener('click', (e) => {
            // Evitar doble click si se pulsa en un elemento interactivo interno
            if (e.target.tagName === 'LABEL' || e.target.closest('a')) return;
            input.click();
        });

        // Cambio de Archivo
        input.addEventListener('change', () => {
            if (input.files && input.files[0]) {
                updateFileName(input.files[0].name);
            }
        });

        // Drag & Drop Visuals
        dz.addEventListener('dragover', (e) => {
            e.preventDefault();
            dz.classList.add('is-dragover');
        });

        dz.addEventListener('dragleave', () => {
            dz.classList.remove('is-dragover');
        });

        dz.addEventListener('drop', (e) => {
            e.preventDefault();
            dz.classList.remove('is-dragover');

            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                input.files = e.dataTransfer.files; // Asignar archivo al input
                updateFileName(e.dataTransfer.files[0].name);
            }
        });

        function updateFileName(name) {
            fileNameDisplay.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:center;gap:6px; color:#16a34a; font-weight:600;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    ${name}
                </div>`;
        }
    };

    // 2. Reestructuración de Datos Bancarios (BACS)
    // Transforma la lista fea de WooCommerce en tarjetas modernas
    const restructureBankDetails = () => {
        const container = document.querySelector('.woocommerce-bacs-bank-details') || document.querySelector('section.woocommerce-bacs-bank-details');
        
        if (!container || container.classList.contains('gdos-processed')) return;
        
        container.classList.add('gdos-processed');

        // Título Principal
        if (!container.querySelector('.gdos-bacs-main-title')) {
            const title = document.createElement('h2');
            title.className = 'gdos-bacs-main-title';
            title.innerText = 'Datos para la transferencia';
            container.prepend(title);
        }

        const grid = document.createElement('div');
        grid.className = 'gdos-bacs-grid';

        // WooCommerce a veces usa ul.wc-bacs-bank-details o ul.order_details
        const accountLists = container.querySelectorAll('ul.wc-bacs-bank-details, ul.order_details');

        if (accountLists.length === 0) return; // Nada que hacer

        accountLists.forEach(ul => {
            const card = document.createElement('div');
            card.className = 'gdos-bacs-card';
            
            const cardUl = document.createElement('ul');

            // Intentamos encontrar el nombre de la cuenta (H3 o div anterior)
            let prev = ul.previousElementSibling;
            let accountName = '';
            
            // Retrocedemos hasta encontrar el nombre o un H3
            while (prev && !prev.classList.contains('wc-bacs-bank-details-account-name') && prev.tagName !== 'H3') {
                // Seguridad para no subir demasiado
                if (prev.classList.contains('gdos-bacs-main-title')) break;
                prev = prev.previousElementSibling;
            }

            if (prev && (prev.classList.contains('wc-bacs-bank-details-account-name') || prev.tagName === 'H3')) {
                accountName = prev.innerText.replace(/:/g, '').trim();
                prev.style.display = 'none'; // Ocultamos el original
            }

            // Agregamos Titular
            if (accountName) {
                const liName = document.createElement('li');
                liName.innerHTML = `<span class="gdos-bacs-label">Titular:</span> <span class="gdos-bacs-value">${accountName}</span>`;
                cardUl.appendChild(liName);
            }

            // Procesamos los items de la lista (Banco, Cuenta, CBU/IBAN)
            const items = ul.querySelectorAll('li');
            
            items.forEach(li => {
                let text = li.innerText;
                let label = '';
                let value = text;

                // Detección inteligente de etiqueta
                if (text.includes(':')) {
                    const parts = text.split(':');
                    label = parts[0].trim();
                    value = parts.slice(1).join(':').trim();
                } else {
                    // Fallback por clases de WooCommerce
                    if (li.classList.contains('bank_name')) label = 'Banco';
                    else if (li.classList.contains('account_number')) label = 'Nº Cuenta';
                    else if (li.classList.contains('iban')) label = 'IBAN / CBU';
                    else if (li.classList.contains('bic')) label = 'BIC / Swift';
                }

                const newLi = document.createElement('li');

                // Si es un número copiables (Cuenta o CBU)
                if (li.classList.contains('account_number') || li.classList.contains('iban')) {
                    newLi.innerHTML = `
                        <span class="gdos-bacs-label">${label}</span>
                        <div class="gdos-number-row">
                            <span class="gdos-bacs-value gdos-mono">${value}</span>
                            <button type="button" class="gdos-copy-btn" data-copy="${value.replace(/\s/g, '')}" aria-label="Copiar ${label}">
                                COPIAR
                            </button>
                        </div>
                    `;
                } else {
                    newLi.innerHTML = `<span class="gdos-bacs-label">${label}</span> <span class="gdos-bacs-value">${value}</span>`;
                }
                cardUl.appendChild(newLi);
            });

            card.appendChild(cardUl);
            grid.appendChild(card);
            ul.style.display = 'none'; // Ocultamos lista original
        });

        container.appendChild(grid);

        // Inicializar botones de copiar
        initCopyButtons(grid);
    };

    const initCopyButtons = (context) => {
        context.querySelectorAll('.gdos-copy-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const text = btn.getAttribute('data-copy');
                
                copyToClipboard(text, () => {
                    // Feedback visual
                    btn.classList.add('copied');
                    const originalText = btn.innerText;
                    btn.innerText = '✓ COPIADO';
                    
                    setTimeout(() => { 
                        btn.classList.remove('copied'); 
                        btn.innerText = originalText; 
                    }, 2000);
                });
            });
        });
    };

    /**
     * Función robusta de copiado (Clipboard API + Fallback)
     */
    const copyToClipboard = (text, onSuccess) => {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onSuccess);
        } else {
            // Fallback para HTTP o navegadores viejos
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-9999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                onSuccess();
            } catch (err) {
                console.error('Error al copiar', err);
            }
            document.body.removeChild(textArea);
        }
    };

    // 3. Arranque Seguro
    const run = () => {
        initDropzone();
        restructureBankDetails();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
    
    // Fallback extra por si el HTML de BACS se inyecta tarde via AJAX
    window.addEventListener('load', run);

})();