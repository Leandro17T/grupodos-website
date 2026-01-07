jQuery(document).ready(function($) {
    
    // --- 1. SIMULADOR DE ZONAS ---
    $('#gdos-sim-btn').on('click', function() {
        var btn = $(this);
        var addr = $('#gdos-sim-address').val();
        var tab = btn.data('tab');
        var resDiv = $('#gdos-sim-result');

        if (!addr) { alert('Escribe una dirección'); return; }

        btn.prop('disabled', true).text('Probando...');
        resDiv.hide().removeClass('success error').html('');

        $.post(gdosAdmin.ajax_url, {
            action: 'gdos_test_address',
            nonce: gdosAdmin.nonce,
            address: addr,
            tab: tab
        }, function(res) {
            btn.prop('disabled', false).text('Probar');
            resDiv.show();

            if (res.success) {
                var d = res.data;
                if (d.found) {
                    resDiv.addClass('success').html('✅ ¡Encontrado! Zona: <strong>' + d.zona + '</strong> | Costo: $' + d.costo);
                    // Opcional: Centrar mapa en el punto encontrado
                    if(map && marker) {
                        var pos = {lat: d.coords.lat, lng: d.coords.lng};
                        map.setCenter(pos);
                        map.setZoom(14);
                        marker.setPosition(pos);
                        marker.setVisible(true);
                    }
                } else {
                    resDiv.addClass('error').html('❌ ' + d.msg);
                }
            } else {
                resDiv.addClass('error').html('❌ Error: ' + res.data);
            }
        });
    });

    // --- 2. MAPA VISUALIZADOR ---
    var mapDiv = document.getElementById('gdos-admin-map');
    var map, marker;

    if (mapDiv && typeof google !== 'undefined') {
        initAdminMap();
    }

    function initAdminMap() {
        // Centro por defecto (Montevideo)
        var center = { lat: -34.9011, lng: -56.1645 };
        
        map = new google.maps.Map(mapDiv, {
            zoom: 11,
            center: center,
            mapTypeId: 'roadmap',
            streetViewControl: false
        });

        marker = new google.maps.Marker({
            map: map,
            visible: false
        });

        // Leer JSON del textarea oculto
        var rawJson = $('#gdos-current-json').val();
        if (rawJson) {
            try {
                var zones = JSON.parse(rawJson);
                var bounds = new google.maps.LatLngBounds();
                var hasPolygons = false;

                zones.forEach(function(z) {
                    if (z.poligono && z.poligono.length > 0) {
                        // Convertir [lat, lng] a {lat, lng}
                        var path = z.poligono.map(function(p) {
                            return { lat: parseFloat(p[0]), lng: parseFloat(p[1]) };
                        });

                        // Dibujar Polígono
                        var poly = new google.maps.Polygon({
                            paths: path,
                            strokeColor: z.color || '#333',
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: z.color || '#333',
                            fillOpacity: 0.35,
                            map: map
                        });

                        // Extender límites para auto-zoom
                        path.forEach(function(pt) { bounds.extend(pt); });
                        hasPolygons = true;
                    }
                });

                if (hasPolygons) {
                    map.fitBounds(bounds);
                }

            } catch (e) {
                console.log('Error parseando JSON de zonas:', e);
            }
        }
    }
});