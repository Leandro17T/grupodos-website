/**
 * REFACTORIZADO: 2025-12-06
 * Módulo: Switch de Cupón en Producto
 * Descripción: Maneja la lógica AJAX para aplicar cupón y agregar al carrito.
 */
jQuery(function ($) {
    'use strict';

    // 1. Configuración y Selectores
    const wrapper = $('#gdos_cupon_wrap');
    if (!wrapper.length) return;

    const form          = $('form.cart');
    const $checkbox     = $('#gdos_cupon_switch');
    const $visualSwitch = $('#gdos_switch_visual');
    const $textLabel    = $('#gdos_switch_label');
    const $msg          = $('#gdos_cupon_msg');
    const $btn          = form.find('button.single_add_to_cart_button');

    // Recuperamos variables localizadas desde PHP
    const config = window.gdosCuponSwitch || {};
    
    // Variables de estado
    let currentVariationId = 0;

    // Guardar estado original del botón
    if (!$btn.data('original-html')) {
        $btn.data('original-html', $btn.html());
    }

    // 2. Funciones de UI
    const lockButton = () => {
        $btn.html('YA EN CARRITO')
            .prop('disabled', true)
            .addClass('gdos-btn-disabled');
    };

    const unlockButton = () => {
        $btn.html($btn.data('original-html') || 'Agregar al carrito')
            .prop('disabled', false)
            .removeClass('gdos-btn-disabled');
    };

    /**
     * Define la interfaz visual de ON.
     * @param {string} mode - 'added' (producto está en carrito) o 'global' (solo cupón activo)
     */
    const setOnUI = (mode) => {
        $visualSwitch.addClass('is-on');
        
        if (mode === 'added') {
            // Producto en carrito -> Bloqueamos botón
            $textLabel.text(config.txt_on_added);
            lockButton();
        } else {
            // Solo cupón activo -> Botón normal
            $textLabel.text(config.txt_on_global);
            unlockButton();
        }
    };

    const setOffUI = () => {
        $visualSwitch.removeClass('is-on');
        $textLabel.text(config.txt_off);
        unlockButton();
    };

    const setLoadingUI = (isLoading) => {
        $checkbox.prop('disabled', isLoading);
        $visualSwitch.css('cursor', isLoading ? 'wait' : 'pointer');
        wrapper.css('opacity', isLoading ? '0.7' : '1');
    };

    // 3. Inicialización
    // Leemos el estado inicial desde el atributo data
    const isProductInCart = wrapper.attr('data-in-cart') === 'yes';

    if ($checkbox.is(':checked')) {
        setOnUI(isProductInCart ? 'added' : 'global');
    } else {
        setOffUI();
    }

    // Listener para detectar cambios en variaciones (WooCommerce nativo)
    form.on('found_variation', function (event, variation) {
        currentVariationId = variation.variation_id;
    });
    
    form.on('reset_data', function () {
        currentVariationId = 0;
    });

    // 4. Lógica Principal (Switch Change)
    $checkbox.on('change', function () {
        const isOn = $(this).is(':checked');
        setLoadingUI(true);
        $msg.hide();

        if (isOn) {
            // --- ACTIVAR: Aplica cupón y AGREGA producto ---
            
            // Recopilación de datos robusta
            const data = {
                action:       'cupon_aplicar_y_agregar',
                nonce:        config.nonce, // SEGURIDAD
                cupon:        'primeracompra',
                quantity:     form.find('input.qty').val() || 1,
                product_id:   config.product_id,
                variation_id: currentVariationId || form.find('input[name="variation_id"]').val() || 0
            };

            // Capturar atributos dinámicos (color, talla, etc)
            form.find('select[name^="attribute_"], input[name^="attribute_"]').each(function () {
                data[this.name] = $(this).val();
            });

            $.post(config.ajaxurl, data, function (response) {
                if (response && response.success) {
                    wrapper.attr('data-in-cart', 'yes');
                    setOnUI('added');
                    $(document.body).trigger('wc_fragment_refresh'); // Actualiza mini-cart
                } else {
                    alert((response && response.data) || 'No se pudo aplicar el cupón.');
                    $checkbox.prop('checked', false);
                    setOffUI();
                }
            })
            .fail(() => {
                alert('Error de conexión. Intente nuevamente.');
                $checkbox.prop('checked', false);
                setOffUI();
            })
            .always(() => {
                setLoadingUI(false);
            });

        } else {
            // --- DESACTIVAR: Remueve solo el cupón ---
            
            const data = {
                action: 'cupon_remover',
                nonce:  config.nonce, // SEGURIDAD
                cupon:  'primeracompra'
            };

            $.post(config.ajaxurl, data, function (response) {
                if (response && response.success) {
                    setOffUI();
                    $msg.text(config.txt_removed).fadeIn();
                    $(document.body).trigger('wc_fragment_refresh');
                } else {
                    alert((response && response.data) || 'No se pudo remover el cupón.');
                    // Revertir estado visual
                    $checkbox.prop('checked', true);
                    const currentInCart = wrapper.attr('data-in-cart') === 'yes';
                    setOnUI(currentInCart ? 'added' : 'global');
                }
            })
            .fail(() => {
                // Revertir en caso de error de red
                $checkbox.prop('checked', true);
                const currentInCart = wrapper.attr('data-in-cart') === 'yes';
                setOnUI(currentInCart ? 'added' : 'global');
            })
            .always(() => {
                setLoadingUI(false);
            });
        }
    });
});