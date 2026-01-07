jQuery(document).ready(function($) {
    // --- VARIABLES ---
    var map, marker, geocoder;
    var mapInitialized = false;
    var shippingAutocomplete, billingAutocomplete;
    
    // --- 1. MODAL ---
    if ($('#gdos-modal-overlay').length === 0) {
        $('body').append('<div id="gdos-modal-overlay"><div id="gdos-modal-content"><div class="gdos-modal-header"><h3>Confirma tu ubicación</h3><p class="gdos-modal-subtitle">Mueve el pin si la ubicación no es exacta.</p></div><div id="gdos-flash-map-canvas"></div><div class="gdos-modal-footer"><button type="button" id="gdos-confirm-btn">Confirmar Ubicación</button></div></div></div>');
    }

    function injectSpinners() {
        var spinnerHTML = '<div class="gdos-input-spinner"></div>';
        if($('#shipping_address_1_field .woocommerce-input-wrapper .gdos-input-spinner').length === 0) {
            $('#shipping_address_1_field .woocommerce-input-wrapper').append(spinnerHTML);
        }
        if($('#billing_address_1_field .woocommerce-input-wrapper .gdos-input-spinner').length === 0) {
            $('#billing_address_1_field .woocommerce-input-wrapper').append(spinnerHTML);
        }
    }

    // --- 2. AUTOCOMPLETE ---
    function initAutocomplete() {
        injectSpinners(); 
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;

        var options = { componentRestrictions: { country: 'uy' }, fields: ['address_components', 'geometry'], strictBounds: false };

        var billingInput = document.getElementById('billing_address_1');
        if (billingInput && !billingInput.classList.contains('pac-target-input')) {
            billingAutocomplete = new google.maps.places.Autocomplete(billingInput, options);
            billingAutocomplete.addListener('place_changed', function() { fillInAddress(billingAutocomplete.getPlace(), 'billing'); });
        }

        var shippingInput = document.getElementById('shipping_address_1');
        var shipToDifferent = $('#ship-to-different-address-checkbox').is(':checked');
        if (shippingInput && shipToDifferent && !shippingInput.classList.contains('pac-target-input')) {
            shippingAutocomplete = new google.maps.places.Autocomplete(shippingInput, options);
            shippingAutocomplete.addListener('place_changed', function() { fillInAddress(shippingAutocomplete.getPlace(), 'shipping'); });
        }
    }

    // --- 3. RELLENAR ---
    function fillInAddress(place, type) {
        if (!place.geometry) return;
        var neighborhood = '', locality = '', state = '', postcode = '';

        for (var i = 0; i < place.address_components.length; i++) {
            var c = place.address_components[i];
            if (c.types.includes('sublocality_level_1') || c.types.includes('sublocality') || c.types.includes('neighborhood')) neighborhood = c.long_name;
            if (c.types.includes('locality')) locality = c.long_name;
            if (c.types.includes('administrative_area_level_1')) state = c.short_name;
            if (c.types.includes('postal_code')) postcode = c.long_name;
        }

        var finalCity = neighborhood ? neighborhood : locality;
        if(finalCity) $('#' + type + '_city').val(finalCity).trigger('change');
        if(state) $('#' + type + '_state').val(state).trigger('change');
        if(postcode) $('#' + type + '_postcode').val(postcode).trigger('change');
        openModal(place.geometry.location);
    }

    // --- 4. MAPA ---
    function initMap() {
        if (typeof google === 'undefined') return;
        geocoder = new google.maps.Geocoder();
        var pos = {lat: -34.9011, lng: -56.1645}; 
        map = new google.maps.Map(document.getElementById('gdos-flash-map-canvas'), {
            zoom: 17, center: pos, streetViewControl: false, mapTypeControl: false, fullscreenControl: false, gestureHandling: 'greedy'
        });
        marker = new google.maps.Marker({ map: map, draggable: true, animation: google.maps.Animation.DROP, position: pos });
        marker.addListener('dragend', function() { reverseGeocode(marker.getPosition()); });
        mapInitialized = true;
    }

    function openModal(location) {
        $('#gdos-modal-overlay').fadeIn(200, function() {
            if(!mapInitialized) initMap();
            google.maps.event.trigger(map, "resize");
            map.setCenter(location);
            marker.setPosition(location);
        });
    }

    function closeModal() { $('#gdos-modal-overlay').fadeOut(200); }

    function reverseGeocode(latlng) {
        geocoder.geocode({'location': latlng}, function(results, status) {
            if (status === 'OK' && results[0]) {
                var prefix = $('#ship-to-different-address-checkbox').is(':checked') ? 'shipping' : 'billing';
                var route = '', num = '';
                for(var i=0; i<results[0].address_components.length; i++) {
                    var t = results[0].address_components[i].types;
                    if (t.includes('route')) route = results[0].address_components[i].long_name;
                    if (t.includes('street_number')) num = results[0].address_components[i].long_name;
                }
                if (route) $('#' + prefix + '_address_1').val(route + ' ' + num).trigger('change');
            }
        });
    }

    $(window).on('load', function(){ setTimeout(initAutocomplete, 1000); });
    $('body').on('change', '#ship-to-different-address-checkbox', function(){ setTimeout(initAutocomplete, 500); });
    $('body').on('click', '#gdos-confirm-btn', closeModal);
    $('body').on('click', '#gdos-modal-overlay', function(e) { if(e.target.id === 'gdos-modal-overlay') closeModal(); });

    // 🔥 FIX VISUAL: ACTUALIZAR TEXTO DEL TOTAL (BIDIRECCIONAL) 🔥
    $(document.body).on('updated_checkout', function() { 
        setTimeout(function() {
            // Detectar método seleccionado
            var selectedVal = $('input[name^="shipping_method"]:checked').val();
            if (!selectedVal) selectedVal = $('select.shipping_method').val();

            // Selectores del resumen
            var $shippingCell = $('.shipping_total_fee td[data-title="Envío"] span');
            var $shippingCellStd = $('.cart-subtotal.shipping td span, .shipping td[data-title] span');
            var $targets = $shippingCell.length ? $shippingCell : $shippingCellStd;

            if ($targets.length === 0) return;

            // 1. CASO TERMINAL: Forzar "Pagas al recibir"
            if (selectedVal && selectedVal.indexOf('gdos_v2_terminal') !== -1) {
                var newText = 'Pagas al recibir';
                $targets.each(function() {
                    var txt = $(this).text().trim().toLowerCase();
                    // Reemplazamos si dice Gratis, Free, vacío O si no tiene la clase aún
                    if (txt === 'gratis' || txt === 'free' || txt === '' || !$(this).hasClass('gdos-collect-on-delivery')) {
                        $(this).text(newText).addClass('gdos-collect-on-delivery');
                    }
                });
            } 
            // 2. CASO NO TERMINAL: Limpiar si quedó pegado "Pagas al recibir"
            else {
                $targets.each(function() {
                    var txt = $(this).text().trim().toLowerCase();
                    // Si dice "pagas al recibir" pero NO estamos en Terminal, volver a "Gratis"
                    // (Asumimos que si estamos aquí es porque el costo es 0, si tuviera precio el plugin lo habría actualizado)
                    if (txt.indexOf('pagas al recibir') !== -1 || $(this).hasClass('gdos-collect-on-delivery')) {
                        $(this).text('Gratis').removeClass('gdos-collect-on-delivery');
                    }
                });
            }
        }, 500);
    });
});