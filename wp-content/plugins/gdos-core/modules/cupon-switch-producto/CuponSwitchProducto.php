<?php
// REFACTORIZADO: 2025-12-06
// /modules/cupon-switch-producto/CuponSwitchProducto.php

namespace GDOS\Modules\CuponSwitchProducto;

use GDOS\Core\ModuleInterface;

if (! \defined('ABSPATH')) {
    exit;
}

class CuponSwitchProducto implements ModuleInterface
{

    private static bool $assets_enqueued = false;
    const NONCE_ACTION = 'gdos_switch_coupon_action';

    public function boot(): void
    {
        if (! \class_exists('WooCommerce')) {
            return;
        }

        \add_shortcode('cupon_switch_aplicar', [$this, 'render_shortcode']);

        // AJAX Endpoints
        \add_action('wp_ajax_cupon_aplicar_y_agregar', [$this, 'ajax_apply_and_add']);
        \add_action('wp_ajax_nopriv_cupon_aplicar_y_agregar', [$this, 'ajax_apply_and_add']);

        \add_action('wp_ajax_cupon_remover', [$this, 'ajax_remove_coupon']);
        \add_action('wp_ajax_nopriv_cupon_remover', [$this, 'ajax_remove_coupon']);
    }

    public function render_shortcode(): string
    {
        if (! \function_exists('is_product') || ! \is_product()) {
            return '';
        }

        global $product;
        if (! \is_a($product, 'WC_Product')) {
            return '';
        }

        // Configuración de Categorías Permitidas
        $allowed_categories = [
            'Cocina y Bazar',
            'Cristalería y Vajilla',
            'Cuidado del hogar',
            'Decoración',
            'Jardin y Aire libre',
            'Mascotas',
            'Organización del hogar',
            'Repostería'
        ];

        // WPO: has_term es eficiente, usa caché de objetos
        if (! \has_term($allowed_categories, 'product_cat', $product->get_id())) {
            return '';
        }

        $this->enqueue_assets($product);

        // Estado Inicial
        $coupon_code = 'primeracompra'; // Hardcoded según lógica original
        $has_coupon  = WC()->cart && WC()->cart->has_discount($coupon_code);

        // ¿Producto en carrito?
        $product_id = $product->get_id();
        $is_in_cart = false;

        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                if ($cart_item['product_id'] == $product_id || $cart_item['variation_id'] == $product_id) {
                    $is_in_cart = true;
                    break;
                }
            }
        }

        // Textos
        $txt_off = \__('Aplicar cupón 10% OFF en tu primer pedido', 'gdos-core');

        if ($has_coupon) {
            if ($is_in_cart) {
                $initial_text = \__('Cupón aplicado con éxito y producto agregado al carrito', 'gdos-core');
            } else {
                $initial_text = \__('Tu cupón de 10% ya está activo en el carrito!', 'gdos-core');
            }
        } else {
            $initial_text = $txt_off;
        }

        \ob_start();
?>
        <div class="gdos-cupon-wrap" id="gdos_cupon_wrap" data-in-cart="<?php echo $is_in_cart ? 'yes' : 'no'; ?>">
            <label class="gdos-cupon-switch">
                <input type="checkbox" id="gdos_cupon_switch" <?php \checked($has_coupon); ?> style="width:0;height:0;opacity:0;position:absolute;">

                <span class="gdos-cupon-label" id="gdos_switch_label"><?php echo \esc_html($initial_text); ?></span>

                <div class="gdos-cupon-toggle <?php echo $has_coupon ? 'is-on' : ''; ?>" id="gdos_switch_visual">
                    <div class="thumb" id="gdos_switch_thumb">
                        <span class="thumb-text thumb-text--off">OFF</span>
                        <span class="thumb-text thumb-text--on">ON</span>
                    </div>
                </div>
            </label>
            <div class="gdos-cupon-msg" id="gdos_cupon_msg"></div>
        </div>
<?php
        return \ob_get_clean();
    }

    public function ajax_apply_and_add()
    {
        \check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $product_id   = isset($_POST['product_id']) ? \absint($_POST['product_id']) : 0;
        $quantity     = isset($_POST['quantity']) ? \wc_stock_amount($_POST['quantity']) : 1;
        $variation_id = isset($_POST['variation_id']) ? \absint($_POST['variation_id']) : 0;
        $coupon_code  = isset($_POST['cupon']) ? \sanitize_text_field($_POST['cupon']) : '';

        // Recopilar atributos de variación
        $variations = [];
        foreach ($_POST as $k => $v) {
            if (\strpos($k, 'attribute_') === 0) {
                $variations[$k] = \wc_clean($v);
            }
        }

        if (! $product_id) {
            \wp_send_json_error(\__('Producto no válido', 'gdos-core'));
        }

        if (WC()->cart) {
            // 1. Agregar al carrito si no está
            $already_in_cart = false;
            foreach (WC()->cart->get_cart() as $item) {
                if ((int) $item['product_id'] === $product_id && (int) $item['variation_id'] === $variation_id) {
                    $already_in_cart = true;
                    break;
                }
            }

            if (! $already_in_cart) {
                $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations);
                if (! $added) {
                    \wp_send_json_error(\__('No se pudo agregar al carrito. Verifica el stock o las opciones.', 'gdos-core'));
                }
            }

            // 2. Aplicar Cupón
            if ($coupon_code && ! WC()->cart->has_discount($coupon_code)) {
                // Verificar validez básica antes de aplicar
                $coupon = new \WC_Coupon($coupon_code);
                if ($coupon->get_id() > 0) {
                    WC()->cart->apply_coupon($coupon_code);
                }
            }

            // Recálculo forzado
            WC()->cart->calculate_totals();
        }

        \wp_send_json_success(\__('Cupón aplicado.', 'gdos-core'));
    }

    public function ajax_remove_coupon()
    {
        \check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $coupon_code = isset($_POST['cupon']) ? \sanitize_text_field($_POST['cupon']) : '';

        if (! $coupon_code) {
            \wp_send_json_error(\__('Cupón inválido', 'gdos-core'));
        }

        if (WC()->cart && WC()->cart->has_discount($coupon_code)) {
            WC()->cart->remove_coupon($coupon_code);
            WC()->cart->calculate_totals();
        }

        \wp_send_json_success(\__('Cupón removido', 'gdos-core'));
    }

    private function enqueue_assets(\WC_Product $product): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        $base_url  = \plugin_dir_url(__FILE__);
        $base_path = \plugin_dir_path(__FILE__);

        // CSS
        $css_file = 'assets/css/frontend.css';
        if (\file_exists($base_path . $css_file)) {
            \wp_enqueue_style(
                'gdos-cupon-switch-css',
                $base_url . $css_file,
                [],
                \filemtime($base_path . $css_file)
            );
        }

        // JS
        $js_file = 'assets/js/frontend.js';
        if (\file_exists($base_path . $js_file)) {
            \wp_enqueue_script(
                'gdos-cupon-switch-js',
                $base_url . $js_file,
                ['jquery', 'wc-add-to-cart'],
                \filemtime($base_path . $js_file),
                true
            );

            // Pasar datos y Nonce al JS
            \wp_localize_script('gdos-cupon-switch-js', 'gdosCuponSwitch', [
                'ajaxurl'       => \admin_url('admin-ajax.php'),
                'nonce'         => \wp_create_nonce(self::NONCE_ACTION),
                'product_id'    => $product->get_id(),
                'txt_off'       => \__('Aplicar cupón 10% OFF en tu primer pedido', 'gdos-core'),
                'txt_on_added'  => \__('Cupón aplicado con éxito y producto agregado al carrito', 'gdos-core'),
                'txt_on_global' => \__('¡Tu cupón de 10% ya está activo en el carrito!', 'gdos-core'),
                'txt_removed'   => \__('Cupón removido con éxito', 'gdos-core')
            ]);
        }

        self::$assets_enqueued = true;
    }
}
