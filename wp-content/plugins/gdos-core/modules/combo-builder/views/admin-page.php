<?php if (!defined('ABSPATH')) exit;

$cfg = is_array($cfg) ? $cfg : [];
$title   = $cfg['title']   ?? 'Arma tu Combo';
$cta     = $cfg['cta_text']?? 'Finalizar pedido';
$steps   = $cfg['steps']   ?? [];
?>
<div class="wrap gdos-combo-admin">
  <h1>Combo Builder (Arma tu Combo)</h1>
  <p>Configura un único combo con pasos. Usa el shortcode <code>[gdos_combo_builder]</code>.</p>

  <form method="post" action="options.php">
    <?php settings_fields('gdos-combo-builder'); ?>
    <table class="form-table" role="presentation">
      <tr>
        <th><label for="gdos_title">Título</label></th>
        <td><input type="text" id="gdos_title" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[title]" value="<?php echo esc_attr($title); ?>" class="regular-text"></td>
      </tr>
      <tr>
        <th><label for="gdos_cta">Texto del botón Finalizar</label></th>
        <td><input type="text" id="gdos_cta" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[cta_text]" value="<?php echo esc_attr($cta); ?>" class="regular-text"></td>
      </tr>
    </table>

    <h2 class="title">Pasos</h2>
    <p class="description">Añade tantos pasos como necesites. Para “Productos del paso” usa IDs separados por coma (ej: <code>123,456,789</code>).</p>

    <div id="gdos-steps">
      <?php if (!empty($steps)) : foreach ($steps as $i => $s) : ?>
        <div class="gdos-step-item">
          <div class="gdos-step-grid">
            <div>
              <label>Título del paso</label>
              <input type="text" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][<?php echo esc_attr($i); ?>][title]" value="<?php echo esc_attr($s['title']); ?>" />
            </div>
            <div>
              <label>Productos del paso (IDs)</label>
              <input type="text" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][<?php echo esc_attr($i); ?>][products]" value="<?php echo esc_attr($s['products']); ?>" />
              <small>Separados por coma</small>
            </div>
            <div class="gdos-cols">
              <label class="gdos-inline">
                <input type="checkbox" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][<?php echo esc_attr($i); ?>][required]" <?php checked(!empty($s['required'])); ?> />
                Obligatorio
              </label>
              <label class="gdos-inline">
                <input type="checkbox" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][<?php echo esc_attr($i); ?>][allow_qty]" <?php checked(!empty($s['allow_qty'])); ?> />
                Permitir cantidades
              </label>
            </div>
          </div>
          <button type="button" class="button-link delete-step">Eliminar paso</button>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <p>
      <button type="button" class="button button-secondary" id="gdos-add-step">+ Agregar paso</button>
    </p>

    <?php submit_button('Guardar configuración'); ?>
  </form>

  <!-- Template oculto para duplicar -->
  <template id="gdos-step-template">
    <div class="gdos-step-item">
      <div class="gdos-step-grid">
        <div>
          <label>Título del paso</label>
          <input type="text" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][__INDEX__][title]" value="" />
        </div>
        <div>
          <label>Productos del paso (IDs)</label>
          <input type="text" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][__INDEX__][products]" value="" />
          <small>Separados por coma</small>
        </div>
        <div class="gdos-cols">
          <label class="gdos-inline">
            <input type="checkbox" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][__INDEX__][required]" />
            Obligatorio
          </label>
          <label class="gdos-inline">
            <input type="checkbox" name="<?php echo esc_attr(GDOS_Combo_Builder::OPTION_KEY); ?>[steps][__INDEX__][allow_qty]" />
            Permitir cantidades
          </label>
        </div>
      </div>
      <button type="button" class="button-link delete-step">Eliminar paso</button>
    </div>
  </template>
</div>
