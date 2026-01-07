<?php
// REFACTORIZADO: 2025-05-21 (Versión "Nivel Dios" con Buscador AJAX)
// /modules/ofertas-flash/includes/Admin.php

namespace GDOS\Modules\OfertasFlash\includes;

use GDOS\Modules\OfertasFlash\OfertasFlash as Config;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class Admin
{
    private const OPT_KEY       = 'gdos_flash_scheduler_options';
    private const OPTION_GROUP  = 'gdos_ofs_group';
    private const PAGE_SLUG     = 'gdos-ofertas-flash-scheduler';
    private const PARENT_SLUG   = 'grupodos-main';

    private const NONCE_ACTION  = 'gdos_flash_save_action';
    private const NONCE_FIELD   = 'gdos_flash_nonce';
    private const ADMINPOST_ACT = 'gdos_flash_save';

    private string $page_hook = '';

    public function __construct()
    {
        \add_action('admin_menu', [$this, 'add_submenu_page']);
        \add_action('admin_init', [$this, 'register_settings'], 5);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        \add_action('admin_post_' . self::ADMINPOST_ACT, [$this, 'handle_save']);
    }

    public function add_submenu_page(): void
    {
        $this->page_hook = \add_submenu_page(
            self::PARENT_SLUG,
            'Ofertas Flash',
            'Ofertas Flash',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        \register_setting(
            self::OPTION_GROUP,
            self::OPT_KEY,
            ['sanitize_callback' => [$this, 'sanitize_options']]
        );
    }

    public function enqueue_assets($hook): void
    {
        if (empty($this->page_hook) || $hook !== $this->page_hook) return;

        // 1. Cargar dependencias de WooCommerce para el buscador (Select2)
        if (\function_exists('wc_enqueue_js')) {
            \wp_enqueue_script('wc-enhanced-select');
            \wp_enqueue_style('woocommerce_admin_styles');
        }

        \wp_enqueue_media();
        \wp_enqueue_script('jquery-ui-sortable');

        $module_root = \dirname(__DIR__) . '/OfertasFlash.php';

        $css = Assets::get('assets/css/admin.css', $module_root);
        \wp_enqueue_style('gdos-ofertas-flash-admin-css', $css['url'], [], $css['ver']);

        $js = Assets::get('assets/js/admin.js', $module_root);
        \wp_enqueue_script('gdos-ofertas-flash-admin-js', $js['url'], ['jquery', 'jquery-ui-sortable', 'wc-enhanced-select'], $js['ver'], true);
    }

    public function handle_save(): void
    {
        if (! \current_user_can('manage_options')) {
            \wp_die(\esc_html__('No tienes permisos suficientes.', 'default'));
        }

        $nonce = isset($_POST[self::NONCE_FIELD]) ? (string) $_POST[self::NONCE_FIELD] : '';
        if (! $nonce || ! \wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            \wp_die(\esc_html__('Error de seguridad (nonce inválido).', 'default'));
        }

        $input     = isset($_POST[self::OPT_KEY]) && \is_array($_POST[self::OPT_KEY]) ? $_POST[self::OPT_KEY] : [];
        $sanitized = $this->sanitize_options($input);

        \update_option(self::OPT_KEY, $sanitized);

        $redirect = \add_query_arg(['page' => self::PAGE_SLUG, 'settings-updated' => 'true'], \admin_url('admin.php'));
        \wp_safe_redirect($redirect);
        exit;
    }

    public function sanitize_options($input): array
    {
        $output = [];
        $output['campaign_title'] = \sanitize_text_field($input['campaign_title'] ?? 'Cyber Fest');

        // Media
        $media_id   = \absint($input['title_media_id'] ?? 0);
        $media_type = \sanitize_text_field($input['title_media_type'] ?? '');

        if ($media_id) {
            $mime = \get_post_mime_type($media_id);
            if ($mime === 'image/gif') {
                $media_type = 'gif';
            } elseif ($mime === 'image/svg+xml') {
                $media_type = 'svg';
            } else {
                $media_id   = 0;
                $media_type = '';
            }
        }
        $output['title_media_id']   = $media_id;
        $output['title_media_type'] = $media_type;
        $output['start_date']       = \sanitize_text_field($input['start_date'] ?? '');

        // Colores
        $output['title_color']  = \sanitize_hex_color($input['title_color'] ?? '')  ?: '#2196F3';
        $output['timer_color']  = \sanitize_hex_color($input['timer_color'] ?? '')  ?: '#111111';
        $output['border_start'] = \sanitize_hex_color($input['border_start'] ?? '') ?: Config::BORDER_START;
        $output['border_end']   = \sanitize_hex_color($input['border_end'] ?? '')   ?: Config::BORDER_END;
        $output['buy_color']    = \sanitize_hex_color($input['buy_color'] ?? '')    ?: Config::BUY_COLOR;

        // Días y Productos (AHORA SON IDs)
        $days = [];
        if (!empty($input['days']) && \is_array($input['days'])) {
            foreach ($input['days'] as $day_products) {
                if (\is_array($day_products)) {
                    // Sanitizamos como enteros (IDs), eliminamos vacíos y reindexamos
                    $clean_ids = \array_values(\array_filter(\array_map('absint', $day_products)));
                    $days[] = $clean_ids;
                }
            }
        }
        $output['days'] = $days;

        return $output;
    }

    public static function get_options(): array
    {
        $defaults = [
            'campaign_title'   => 'Cyber Fest',
            'title_media_id'   => 0,
            'title_media_type' => '',
            'start_date'       => '',
            'title_color'      => '#2196F3',
            'timer_color'      => '#111111',
            'border_start'     => Config::BORDER_START,
            'border_end'       => Config::BORDER_END,
            'buy_color'        => Config::BUY_COLOR,
            'days'             => [],
        ];

        $opts = \get_option(self::OPT_KEY, $defaults);
        return \wp_parse_args($opts, $defaults);
    }

    public function render_page(): void
    {
        if (! \current_user_can('manage_options')) \wp_die('No tienes permisos.');

        $options = self::get_options();
        $media_id   = $options['title_media_id'] ?? 0;
        $media_url  = $media_id ? \wp_get_attachment_url($media_id) : '';
        $form_action = \admin_url('admin-post.php');
?>
        <div class="wrap" id="gdos-flash-admin-root">
            <h1>Programador de Ofertas Flash</h1>
            <?php if (!empty($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Ajustes guardados.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo \esc_url($form_action); ?>">
                <?php \wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                <input type="hidden" name="action" value="<?php echo \esc_attr(self::ADMINPOST_ACT); ?>">

                <h2>Configuración General</h2>
                <table class="form-table">
                    <tr>
                        <th><label>Título Campaña</label></th>
                        <td><input type="text" name="<?php echo \esc_attr(self::OPT_KEY); ?>[campaign_title]" value="<?php echo \esc_attr($options['campaign_title']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Icono (GIF/SVG)</label></th>
                        <td>
                            <div class="gdos-image-uploader">
                                <div class="gdos-image-preview">
                                    <?php if ($media_url) echo '<img src="' . \esc_url($media_url) . '" style="max-width:60px;">'; ?>
                                </div>
                                <input type="hidden" name="<?php echo \esc_attr(self::OPT_KEY); ?>[title_media_id]" value="<?php echo \esc_attr($media_id); ?>">
                                <input type="hidden" name="<?php echo \esc_attr(self::OPT_KEY); ?>[title_media_type]" value="<?php echo \esc_attr($options['title_media_type'] ?? ''); ?>">
                                <button type="button" class="button gdos-upload-media-button">Subir</button>
                                <button type="button" class="button gdos-remove-media-button" style="<?php echo $media_id ? '' : 'display:none;'; ?>">Quitar</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Fecha Inicio</label></th>
                        <td><input type="date" name="<?php echo \esc_attr(self::OPT_KEY); ?>[start_date]" value="<?php echo \esc_attr($options['start_date']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Color Título</label></th>
                        <td><input type="color" name="<?php echo \esc_attr(self::OPT_KEY); ?>[title_color]" value="<?php echo \esc_attr($options['title_color']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Color Timer</label></th>
                        <td><input type="color" name="<?php echo \esc_attr(self::OPT_KEY); ?>[timer_color]" value="<?php echo \esc_attr($options['timer_color']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Marco Inicio</label></th>
                        <td><input type="color" name="<?php echo \esc_attr(self::OPT_KEY); ?>[border_start]" value="<?php echo \esc_attr($options['border_start']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Marco Fin</label></th>
                        <td><input type="color" name="<?php echo \esc_attr(self::OPT_KEY); ?>[border_end]" value="<?php echo \esc_attr($options['border_end']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Botón Comprar</label></th>
                        <td><input type="color" name="<?php echo \esc_attr(self::OPT_KEY); ?>[buy_color]" value="<?php echo \esc_attr($options['buy_color']); ?>"></td>
                    </tr>
                </table>

                <hr>
                <h2>Planificador de Días</h2>
                <div id="gdos-days-container">
                    <?php
                    $days = $options['days'] ?? [];
                    if (!empty($days)) {
                        foreach ($days as $day_index => $products) {
                            $this->render_day_template($day_index, $products);
                        }
                    } else {
                        $this->render_day_template(0, []);
                    }
                    ?>
                </div>
                <p><button type="button" class="button button-secondary" id="gdos-add-day">+ Añadir Día</button></p>

                <?php \submit_button(); ?>
            </form>
        </div>

        <script type="text/html" id="tmpl-gdos-day-card">
            <?php $this->render_day_template('__DAY_INDEX__', []); ?>
        </script>
        <script type="text/html" id="tmpl-gdos-sku-slot">
            <?php $this->render_product_slot('__DAY_INDEX__', 0); ?>
        </script>
    <?php
    }

    private function render_day_template($day_index, $products): void
    {
        $display_index = \is_numeric($day_index) ? ((int)$day_index + 1) : '';
    ?>
        <div class="day-card" data-day-index="<?php echo \esc_attr($day_index); ?>">
            <div class="day-header">
                <span class="day-handle"></span>
                <strong class="day-title">Día <?php echo \esc_html($display_index); ?> <span class="js-day-number"></span></strong>
                <button type="button" class="button-link-delete day-remove">Eliminar Día</button>
            </div>
            <div class="day-products">
                <?php if (!empty($products) && \is_array($products)) :
                    foreach ($products as $product_id): ?>
                        <?php $this->render_product_slot($day_index, $product_id); ?>
                    <?php endforeach;
                else: ?>
                    <?php $this->render_product_slot($day_index, 0); ?>
                <?php endif; ?>
            </div>
            <div class="day-actions">
                <button type="button" class="button sku-add-button">+ Añadir Producto</button>
            </div>
        </div>
    <?php
    }

    // NUEVO: Renderiza el Select2 en lugar del Input Text
    private function render_product_slot($day_index, $product_id): void
    {
        $product_id = \absint($product_id);
        $product_name = '';

        // Pre-cargar nombre si existe el producto
        if ($product_id > 0 && ($product = \wc_get_product($product_id))) {
            $product_name = $product->get_formatted_name();
        }
    ?>
        <div class="sku-slot" style="margin-bottom:8px; display:flex; align-items:center; gap:8px;">
            <select class="wc-product-search"
                style="width: 100%;"
                name="<?php echo \esc_attr(self::OPT_KEY); ?>[days][<?php echo \esc_attr($day_index); ?>][]"
                data-placeholder="Buscar producto..."
                data-action="woocommerce_json_search_products_and_variations">
                <?php if ($product_id): ?>
                    <option value="<?php echo \esc_attr($product_id); ?>" selected="selected">
                        <?php echo \esc_html(\strip_tags($product_name)); ?>
                    </option>
                <?php endif; ?>
            </select>
            <button type="button" class="button-link-delete sku-remove-button" title="Quitar">&times;</button>
        </div>
<?php
    }
}
