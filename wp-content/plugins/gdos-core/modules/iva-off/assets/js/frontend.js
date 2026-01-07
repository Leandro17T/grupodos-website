(function($) {
    
    function bindVariationPriceUpdate() {
        var bar = document.getElementById('gdos-ivaoff-topbar');
        if (!bar) return;
        var rate = parseFloat(bar.dataset.gdosRate) || 0;
        var priceEl = document.getElementById('gdos-ivaoff-price');
        var form = document.querySelector('form.variations_form');

        var formatPrice = function(amount){
            var p = window.wc_price_params || {};
            var symbol = p.currency_format_symbol || '$';
            var precision = p.currency_format_num_decimals || 2;
            var thousand = p.currency_format_thousand_sep || '.';
            var decimal = p.currency_format_decimal_sep || ',';
            var format = p.currency_format || '%s%v';
            var price = amount.toFixed(precision).replace('.', decimal);
            var parts = price.split(decimal);
            parts[0] = parts[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1' + thousand);
            price = parts.join(decimal);
            return format.replace('%s', symbol).replace('%v', price);
        };

        if (form && priceEl) {
            $(form).off('found_variation.gdos').on('found_variation.gdos', function(evt, variation){
                var basePrice = variation && variation.display_price ? variation.display_price : 0;
                if (basePrice > 0) priceEl.textContent = formatPrice(basePrice * (1 - rate));
            });
        }
    }

    function bindCheckoutRefreshers() {
        $(document).on('change', 'input[name="payment_method"]', function() {
            $('body').trigger('update_checkout');
        });
        $(document).on('change', 'input[name^="shipping_method"]', function() {
            $('body').trigger('update_checkout');
        });
        $(document).on('change', 'select.shipping_method', function() {
            $('body').trigger('update_checkout');
        });
    }

    // 3. Inyectar nota informativa (NUEVA LÓGICA Y CLASE)
    function injectCouponInfoNote() {
        var data = window.gdosIvaOff || {};
        var isActive = (data.active == 1);
        var showNotice = (data.coupon_notice_enabled == 1);
        var customText = data.coupon_notice_text || 'Si pagás con transferencia, el beneficio IVA OFF reemplaza cualquier cupón.';

        // LIMPIEZA TOTAL: Borramos la clase vieja y la nueva para evitar duplicados
        $('.gdos-ivaoff-coupon-info').remove(); 
        $('.gdos-ivaoff-avisocupon-final').remove();

        if (!isActive || !showNotice) return;

        var $targets = $('form.checkout_coupon, .woocommerce-form-coupon, .cart .coupon, .checkout_coupon_inner');

        if ($targets.length > 0) {
            $targets.each(function(){
                var $t = $(this);
                // INYECCIÓN LIMPIA: Usamos la nueva clase, sin estilos inline
                $t.append('<p class="gdos-ivaoff-avisocupon-final">' + customText + '</p>');
            });
        }
    }

    $(document).ready(function() {
        bindVariationPriceUpdate();
        bindCheckoutRefreshers();
        
        injectCouponInfoNote();

        $(document.body).on('updated_checkout', function(){
            setTimeout(injectCouponInfoNote, 500);
        });
    });

})(jQuery);