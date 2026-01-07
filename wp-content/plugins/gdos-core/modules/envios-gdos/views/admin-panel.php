<?php if (! defined('ABSPATH')) exit;
$prefix = 'gdos_' . $active_tab . '_';
?>

<style>
    .gdos-card {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 20px;
        max-width: 1000px;
        position: relative;
    }

    .gdos-card h2 {
        margin-top: 0;
        border-bottom: 1px solid #f0f0f1;
        padding-bottom: 15px;
        margin-bottom: 20px;
        font-size: 1.2em;
    }

    .gdos-flex-row { display: flex; gap: 20px; align-items: flex-start; }
    .gdos-col { flex: 1; }
    .gdos-upload-box { border: 2px dashed #c3c4c7; padding: 20px; text-align: center; background: #fbfbfb; border-radius: 4px; }
    .gdos-details summary { cursor: pointer; font-weight: 600; color: #2271b1; padding: 10px 0; outline: none; }
    .gdos-table-wrapper { border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; }
    .gdos-table-wrapper table { border: none; box-shadow: none; }
    #gdos-admin-map { width: 100%; height: 400px; border: 1px solid #ccc; border-radius: 4px; margin-top: 15px; background: #eee; }
    .gdos-simulator { background: #f0f6fc; border: 1px solid #cce5ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .gdos-sim-result { margin-top: 10px; font-weight: 600; display: none; }
    .gdos-sim-result.success { color: #16a34a; }
    .gdos-sim-result.error { color: #dc2626; }
    
    /* Estilos para la tabla de categorias especiales */
    .gdos-special-table td { vertical-align: middle; }
    .gdos-special-table input[type="number"] { width: 100px; }
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">Envíos GRUPO DOS</h1>
    <?php if (isset($_GET['msg'])): ?>
        <div id="message" class="updated notice is-dismissible" style="margin-top:15px;">
            <p>
                <?php
                if ($_GET['msg'] === 'error_json') echo '❌ Error: JSON inválido.';
                elseif ($_GET['msg'] === 'cache_cleared') echo '🧹 Caché de direcciones limpiada con éxito.';
                else echo '✅ Guardado correctamente.';
                ?>
            </p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper" style="margin-top:15px; margin-bottom:20px;">
        <a href="?page=gdos-envios-panel&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">⚙️ General</a>
        <a href="?page=gdos-envios-panel&tab=express" class="nav-tab <?php echo $active_tab === 'express' ? 'nav-tab-active' : ''; ?>">🚚 Express</a>
        <a href="?page=gdos-envios-panel&tab=flash" class="nav-tab <?php echo $active_tab === 'flash' ? 'nav-tab-active' : ''; ?>">⚡ Flash</a>
        <a href="?page=gdos-envios-panel&tab=pickup" class="nav-tab <?php echo $active_tab === 'pickup' ? 'nav-tab-active' : ''; ?>">🏪 Retiro</a>
        <a href="?page=gdos-envios-panel&tab=terminal" class="nav-tab <?php echo $active_tab === 'terminal' ? 'nav-tab-active' : ''; ?>">🚌 Terminal</a>
    </nav>

    <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="gdos_envios_save">
        <input type="hidden" name="current_tab" value="<?php echo esc_attr($active_tab); ?>">
        <?php wp_nonce_field('gdos_envios_save_nonce'); ?>

        <?php if ($active_tab === 'general'): ?>
            <div class="gdos-card">
                <h2>Configuración Global</h2>
                <table class="form-table">
                    <tr>
                        <th><label>Google Maps API Key</label></th>
                        <td>
                            <input type="password" name="gdos_global_api_key" value="<?php echo esc_attr($data['api_key']); ?>" class="regular-text">
                            <p class="description">Backend & Frontend Key.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Mantenimiento</th>
                        <td>
                            <a href="<?php echo admin_url('admin-post.php?action=gdos_clear_geo_cache'); ?>" class="button">🗑️ Limpiar Caché de Direcciones</a>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gdos-card">
                <h2>Electrodomésticos y TV (Costos Especiales)</h2>
                <p class="description" style="margin-bottom:15px;">Asigna costos fijos a categorías pesadas. <strong>Nota:</strong> Estas categorías NO tendrán envío gratis.</p>
                
                <table class="widefat striped gdos-special-table">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Costo Flash</th>
                            <th>Costo Express</th>
                            <th>Costo Terminal</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="gdos-special-rows">
                        <?php 
                        $special_rules = $data['special_rules'] ?? [];
                        if (!empty($special_rules)): foreach ($special_rules as $rule): 
                        ?>
                            <tr>
                                <td>
                                    <select name="special_cat_id[]" style="width:100%;">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat->term_id; ?>" <?php selected($rule['cat_id'], $cat->term_id); ?>>
                                                <?php echo esc_html($cat->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>$ <input type="number" name="special_cost_flash[]" value="<?php echo esc_attr($rule['flash']); ?>" step="0.01"></td>
                                <td>$ <input type="number" name="special_cost_express[]" value="<?php echo esc_attr($rule['express']); ?>" step="0.01"></td>
                                <td>$ <input type="number" name="special_cost_terminal[]" value="<?php echo esc_attr($rule['terminal']); ?>" step="0.01"></td>
                                <td><button type="button" class="button gdos-remove-row">❌</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div style="margin-top:10px;">
                    <button type="button" id="gdos-add-special" class="button button-secondary">➕ Agregar Categoría</button>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($){
                $('#gdos-add-special').on('click', function(){
                    var row = '<tr>' +
                        '<td><select name="special_cat_id[]" style="width:100%;"><option value="">-- Seleccionar --</option>' +
                        '<?php foreach ($categories as $cat): ?><option value="<?php echo $cat->term_id; ?>"><?php echo esc_js($cat->name); ?></option><?php endforeach; ?>' +
                        '</select></td>' +
                        '<td>$ <input type="number" name="special_cost_flash[]" value="0" step="0.01"></td>' +
                        '<td>$ <input type="number" name="special_cost_express[]" value="0" step="0.01"></td>' +
                        '<td>$ <input type="number" name="special_cost_terminal[]" value="0" step="0.01"></td>' +
                        '<td><button type="button" class="button gdos-remove-row">❌</button></td>' +
                        '</tr>';
                    $('#gdos-special-rows').append(row);
                });
                $(document).on('click', '.gdos-remove-row', function(){
                    $(this).closest('tr').remove();
                });
            });
            </script>

        <?php else: ?>

            <div class="gdos-card gdos-simulator">
                <h3 style="margin-top:0;">🧪 Simulador de Cobertura</h3>
                <p>Prueba una dirección para ver si el sistema la detecta correctamente en este método.</p>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="gdos-sim-address" class="large-text" placeholder="Ej: Av. 18 de Julio 1234, Montevideo">
                    <button type="button" id="gdos-sim-btn" class="button button-secondary" data-tab="<?php echo esc_attr($active_tab); ?>">Probar</button>
                </div>
                <div id="gdos-sim-result" class="gdos-sim-result"></div>
            </div>

            <div class="gdos-card">
                <h2>Ajustes: <?php echo ucfirst($active_tab); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label>Título Base</label></th>
                        <td><input type="text" name="<?php echo $prefix; ?>method_title" value="<?php echo esc_attr($data['title']); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th><label>Descripción Checkout (Base)</label></th>
                        <td><input type="text" name="<?php echo $prefix; ?>frontend_desc" value="<?php echo esc_attr($data['desc']); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th><label>Umbral Envío Gratis</label></th>
                        <td>$ <input type="number" step="0.01" name="<?php echo $prefix; ?>free_threshold" value="<?php echo esc_attr($data['free_threshold']); ?>"></td>
                    </tr>
                </table>

                <h3 style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;"> Descripciones Dinámicas por Horario</h3>
                <table class="form-table">
                    <tr>
                        <th><label>Activar Cambio de Descripción</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $prefix; ?>dynamic_enabled" value="yes" <?php checked($data['dynamic_enabled'], 'yes'); ?>>
                                Habilitar lógica de horarios para cambiar la descripción.
                            </label>
                        </td>
                    </tr>

                    <tr style="background:#f9f9f9;">
                        <th><label>📅 Lunes a Viernes</label></th>
                        <td><strong>Configuración Estándar</strong></td>
                    </tr>
                    <tr>
                        <th><label>Hora de Corte (24hs)</label></th>
                        <td>
                            <input type="time" name="<?php echo $prefix; ?>cutoff_time" value="<?php echo esc_attr($data['cutoff_time']); ?>">
                            <p class="description">Lun-Vie: Antes de esta hora es "Hoy", después es "Mañana".</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Descripción: Llega Hoy</label></th>
                        <td>
                            <input type="text" name="<?php echo $prefix; ?>desc_today" value="<?php echo esc_attr($data['desc_today']); ?>" class="large-text" placeholder="Ej: ¡Comprá ahora y recibilo hoy!">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Descripción: Llega Mañana</label></th>
                        <td>
                            <input type="text" name="<?php echo $prefix; ?>desc_tomorrow" value="<?php echo esc_attr($data['desc_tomorrow']); ?>" class="large-text" placeholder="Ej: Te lo enviamos mañana a primera hora.">
                        </td>
                    </tr>

                    <tr style="background:#f0f6fc; border-top:1px solid #ddd;">
                        <th><label>📅 Sábados</label></th>
                        <td><strong>Excepción de Fin de Semana</strong></td>
                    </tr>
                    <tr style="background:#f0f6fc;">
                        <th><label>Hora Corte Sábado</label></th>
                        <td>
                            <input type="time" name="<?php echo $prefix; ?>cutoff_sat" value="<?php echo esc_attr($data['cutoff_sat']); ?>">
                            <p class="description">Hora límite del sábado (Ej: 12:00).</p>
                        </td>
                    </tr>
                    <tr style="background:#f0f6fc;">
                        <th><label>Descripción: Sábado Tarde</label></th>
                        <td>
                            <input type="text" name="<?php echo $prefix; ?>desc_sat_pm" value="<?php echo esc_attr($data['desc_sat_pm']); ?>" class="large-text" placeholder="Ej: Tu pedido se procesará y enviará el Lunes.">
                            <p class="description">Se muestra el Sábado después del corte, hasta las 23:59.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="gdos-card">
                <h2>Zonas y Mapa</h2>
                <p class="description">Visualización de las zonas cargadas actualmente.</p>
                <div id="gdos-admin-map"></div>
                <textarea id="gdos-current-json" style="display:none;"><?php echo esc_textarea($data['json']); ?></textarea>

                <br><br>
                <div class="gdos-flex-row">
                    <div class="gdos-col">
                        <div class="gdos-upload-box">
                            <p><strong>Actualizar JSON</strong></p>
                            <input type="file" name="gdos_zonas_file" accept=".json">
                        </div>
                    </div>
                    <div class="gdos-col">
                        <details class="gdos-details">
                            <summary>Editar JSON manualmente</summary>
                            <textarea name="gdos_zonas_json_text" rows="5" class="large-text" style="font-family:monospace; font-size:11px; margin-top:10px;"><?php echo esc_textarea($data['json']); ?></textarea>
                        </details>
                    </div>
                </div>

                <br>
                <?php if (!empty($zonas)): ?>
                    <div class="gdos-table-wrapper">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th style="width:100px;">ID Zona</th>
                                    <th>Nombre</th>
                                    <th style="width:150px;">Costo</th>
                                    <th style="width:80px;">Color</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zonas as $z):
                                    $id = esc_attr($z['id'] ?? '');
                                    $costo = isset($z['costo']) ? floatval($z['costo']) : 0;
                                ?>
                                    <tr>
                                        <td><code><?php echo $id; ?></code></td>
                                        <td><strong><?php echo esc_html($z['nombre'] ?? 'Sin nombre'); ?></strong></td>
                                        <td>$ <input type="number" step="0.01" name="gdos_cost[<?php echo $id; ?>]" value="<?php echo $costo; ?>" style="width:100px;"></td>
                                        <td><span style="display:inline-block;width:20px;height:20px;background:<?php echo esc_attr($z['color'] ?? '#ccc'); ?>;border-radius:50%;"></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <p class="submit">
            <button type="submit" class="button button-primary button-hero">Guardar Cambios</button>
        </p>
    </form>
</div>