<?php
// REFACTORIZADO: 2025-05-21
// /modules/precios-usd/PreciosUSD.php

namespace GDOS\Modules\PreciosUSD;

use GDOS\Core\ModuleInterface;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class PreciosUSD implements ModuleInterface
{
    // Constantes para evitar errores de tipeo en Meta Keys
    private const META_CURRENCY       = '_moneda_producto';
    private const META_PRICE_USD      = '_precio_usd';
    private const META_PRICE_USD_SALE = '_precio_usd_rebajado';

    // Opción global para la tasa de cambio
    private const OPTION_RATE         = 'gdos_cotizacion_usd';

    public function boot(): void
    {
        // --- Hooks del Backend (Administrador) ---
        \add_action('wp_dashboard_setup', [$this, 'setup_dashboard_widget']);
        \add_action('woocommerce_product_options_pricing', [$this, 'add_product_meta_fields']);
        \add_action('woocommerce_process_product_meta', [$this, 'save_product_meta_fields']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // --- Hooks del Frontend (Carga de Assets) ---
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);

        // --- Hooks del Frontend (Visualización) ---
        \add_filter('woocommerce_get_price_html', [$this, 'display_usd_price_html'], 20, 2);

        // --- Hooks de Conversión (Lógica de Negocio - Carrito/Checkout) ---
        \add_filter('woocommerce_product_get_price', [$this, 'convert_price_to_uyu_original'], 20, 2);
        \add_filter('woocommerce_product_get_regular_price', [$this, 'convert_price_to_uyu_original'], 20, 2);
        \add_filter('woocommerce_product_get_sale_price', [$this, 'convert_price_to_uyu_original'], 20, 2);
    }

    // -------------------------------------------------------------------------
    // WIDGET DE ESCRITORIO
    // -------------------------------------------------------------------------

    public function setup_dashboard_widget(): void
    {
        if (! \current_user_can('manage_woocommerce')) return;

        \wp_add_dashboard_widget(
            'gdos_widget_cotizacion_usd_id',
            '💵 Cotización del Dólar (UYU)',
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget(): void
    {
        if (isset($_POST['gdos_cotizacion_usd']) && \check_admin_referer('gdos_save_cotizacion_usd_nonce')) {
            $new_rate = \abs(\floatval($_POST['gdos_cotizacion_usd']));
            \update_option(self::OPTION_RATE, $new_rate);
            echo '<div class="notice notice-success inline"><p>✅ Cotización actualizada.</p></div>';
        }

        $cotizacion_actual = \get_option(self::OPTION_RATE, '');

?>
        <form method="post">
            <?php \wp_nonce_field('gdos_save_cotizacion_usd_nonce'); ?>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <label for="gdos_cotizacion_usd" style="font-weight:600;">1 USD = </label>
                <input type="number"
                    id="gdos_cotizacion_usd"
                    name="gdos_cotizacion_usd"
                    step="0.01"
                    min="0"
                    value="<?php echo \esc_attr($cotizacion_actual); ?>"
                    style="width: 100px; text-align:right;"
                    required>
                <span style="font-weight:600;">UYU</span>
            </div>
            <p class="description" style="margin-bottom:10px;">Esta cotización se usará para calcular el precio final en pesos en el carrito.</p>
            <input type="submit" class="button button-primary" value="Guardar Cotización">
        </form>
<?php
    }

    // -------------------------------------------------------------------------
    // GESTIÓN DE METADATA (PRODUCTO)
    // -------------------------------------------------------------------------

    public function add_product_meta_fields(): void
    {
        echo '<div class="options_group gdos-moneda-fields-wrapper">';

        \woocommerce_wp_select([
            'id'          => self::META_CURRENCY,
            'label'       => 'Moneda del producto',
            'description' => 'Seleccioná si este producto tiene precio base en Dólares.',
            'options'     => [
                'uyu' => 'Pesos Uruguayos (UYU)',
                'usd' => 'Dólares (USD)'
            ],
            'desc_tip'    => true
        ]);

        \woocommerce_wp_text_input([
            'id'                => self::META_PRICE_USD,
            'label'             => 'Precio normal en USD',
            'type'              => 'number',
            'custom_attributes' => ['step' => 'any', 'min' => '0'],
            'wrapper_class'     => 'gdos-usd-field'
        ]);

        \woocommerce_wp_text_input([
            'id'                => self::META_PRICE_USD_SALE,
            'label'             => 'Precio rebajado en USD',
            'type'              => 'number',
            'custom_attributes' => ['step' => 'any', 'min' => '0'],
            'wrapper_class'     => 'gdos-usd-field'
        ]);

        echo '</div>';
    }

    public function save_product_meta_fields(int $post_id): void
    {
        $moneda = isset($_POST[self::META_CURRENCY]) ? \wc_clean(\wp_unslash($_POST[self::META_CURRENCY])) : 'uyu';
        \update_post_meta($post_id, self::META_CURRENCY, $moneda);

        if ($moneda === 'usd') {
            $price_usd      = isset($_POST[self::META_PRICE_USD]) ? \wc_clean(\wp_unslash($_POST[self::META_PRICE_USD])) : '';
            $price_usd_sale = isset($_POST[self::META_PRICE_USD_SALE]) ? \wc_clean(\wp_unslash($_POST[self::META_PRICE_USD_SALE])) : '';

            \update_post_meta($post_id, self::META_PRICE_USD, $price_usd);
            \update_post_meta($post_id, self::META_PRICE_USD_SALE, $price_usd_sale);
        }
    }

    // -------------------------------------------------------------------------
    // CARGA DE ASSETS
    // -------------------------------------------------------------------------

    public function enqueue_admin_scripts(string $hook): void
    {
        global $post;
        if (('post.php' === $hook || 'post-new.php' === $hook) && isset($post->post_type) && 'product' === $post->post_type) {
            $js = Assets::get('assets/js/admin.js', __FILE__);
            \wp_enqueue_script('gdos-precios-usd-admin-js', $js['url'], ['jquery'], $js['ver'], true);
        }
    }

    public function enqueue_frontend_scripts(): void
    {
        if (! \is_woocommerce() && ! \is_cart() && ! \is_checkout() && ! \is_front_page()) {
            return;
        }
        $css = Assets::get('assets/css/frontend.css', __FILE__);
        \wp_enqueue_style('gdos-precios-usd-frontend', $css['url'], [], $css['ver']);
    }

    // -------------------------------------------------------------------------
    // VISUALIZACIÓN EN FRONTEND (HTML)
    // -------------------------------------------------------------------------

    public function display_usd_price_html(string $price_html, $product): string
    {
        if (! \is_a($product, 'WC_Product')) return $price_html;

        if ($product->get_meta(self::META_CURRENCY) !== 'usd') {
            return $price_html;
        }

        $precio_usd = (float) $product->get_meta(self::META_PRICE_USD);

        if ($precio_usd <= 0) {
            return '<span class="amount">Consultar</span>';
        }

        $precio_usd_rebajado = (float) $product->get_meta(self::META_PRICE_USD_SALE);
        $cotizacion          = (float) \get_option(self::OPTION_RATE, 0);

        $precio_formateado = 'USD&nbsp;' . \number_format($precio_usd, 2);

        $precio_html_final = '<span class="woocommerce-Price-amount amount"><bdi>' . $precio_formateado . '</bdi></span>';
        $valor_a_estimar   = $precio_usd;

        if ($precio_usd_rebajado > 0 && $precio_usd_rebajado < $precio_usd) {
            $precio_rebajado_fmt = 'USD&nbsp;' . \number_format($precio_usd_rebajado, 2);

            $precio_html_final = \sprintf(
                '<del aria-hidden="true"><span class="woocommerce-Price-amount amount"><bdi>%s</bdi></span></del> <ins><span class="woocommerce-Price-amount amount"><bdi>%s</bdi></span></ins>',
                $precio_formateado,
                $precio_rebajado_fmt
            );

            $valor_a_estimar = $precio_usd_rebajado;
        }

        // Agregar precio exacto en UYU (Sin "aprox")
        if ($cotizacion > 0) {
            $valor_estimado = \round($valor_a_estimar * $cotizacion);
            $estimado_fmt   = \number_format($valor_estimado, 0, ',', '.');

            $precio_html_final .= \sprintf(
                '<small class="gdos-precio-estimado-uyu">($ %s UYU)</small>',
                $estimado_fmt
            );
        }

        return '<p class="price">' . $precio_html_final . '</p>';
    }

    // -------------------------------------------------------------------------
    // CONVERSIÓN DE MONEDA (CARRITO)
    // -------------------------------------------------------------------------

    public function convert_price_to_uyu_original($price, $product)
    {
        if (! \is_a($product, 'WC_Product')) return $price;

        $moneda = $product->get_meta(self::META_CURRENCY);

        if ($moneda !== 'usd') {
            return $price;
        }

        $cotizacion = (float) \get_option(self::OPTION_RATE, 0);

        if ($cotizacion <= 0) return $price;

        $precio_usd          = (float) $product->get_meta(self::META_PRICE_USD);
        $precio_usd_rebajado = (float) $product->get_meta(self::META_PRICE_USD_SALE);

        $precio_efectivo_usd = $precio_usd;

        if ($precio_usd_rebajado > 0 && $precio_usd_rebajado < $precio_usd) {
            $precio_efectivo_usd = $precio_usd_rebajado;
        }

        return \round($precio_efectivo_usd * $cotizacion, 2);
    }
}
