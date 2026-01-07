<?php

namespace GDOS\Modules\EnviosGdos\Includes;

use GDOS\Core\Assets;

if (! defined('ABSPATH')) exit;

class CheckoutFrontend
{
    public function __construct()
    {
        add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'modern_label'], 10, 2);
        add_filter('woocommerce_cart_totals_shipping_html', [$this, 'totals'], 9999, 1);
        add_action('wp_enqueue_scripts', [$this, 'scripts'], 100);
    }

    public function modern_label($label, $method)
    {
        $desc = '';
        $is_gdos = false;

        $prefix_map = [
            'gdos_v2_express'  => 'gdos_express_',
            'gdos_v2_flash'    => 'gdos_flash_',
            'gdos_v2_pickup'   => 'gdos_pickup_',
            'gdos_v2_terminal' => 'gdos_terminal_'
        ];

        $prefix = $prefix_map[$method->method_id] ?? null;

        if ($prefix) {
            $desc = get_option($prefix . 'frontend_desc', ''); // Descripción base
            $is_gdos = true;
        }

        if (!$is_gdos) return $label;

        // --- LÓGICA DINÁMICA: CAMBIO DE DESCRIPCIÓN ---
        if ($prefix) {
            $dynamic_enabled = get_option($prefix . 'dynamic_enabled', 'no');

            if ($dynamic_enabled === 'yes') {
                $cutoff = get_option($prefix . 'cutoff_time', '16:00');

                // Nuevas variables para descripciones
                $desc_today = get_option($prefix . 'desc_today', '');
                $desc_tomorrow = get_option($prefix . 'desc_tomorrow', '');

                $cutoff_sat = get_option($prefix . 'cutoff_sat', '12:00');
                $desc_sat_pm = get_option($prefix . 'desc_sat_pm', '');

                $now = current_time('H:i');
                $day_of_week = current_time('w'); // 6=Sáb

                // 1. SÁBADO TARDE
                if ($day_of_week == 6 && $now >= $cutoff_sat && !empty($desc_sat_pm)) {
                    $desc = $desc_sat_pm;
                }
                // 2. ESTÁNDAR
                elseif (!empty($desc_today) && !empty($desc_tomorrow)) {
                    if ($now < $cutoff) {
                        $desc = $desc_today;
                    } else {
                        $desc = $desc_tomorrow;
                    }
                }
            }
        }

        // --- PRECIOS ---
        $price_html = '';
        $price_class = 'gdos-price-amount';

        if ($method->method_id === 'gdos_v2_terminal') {
            $price_html = 'Pagas al recibir';
            $price_class = 'gdos-price-cod';
        } elseif ($method->cost > 0) {
            $price_html = wc_price($method->cost);
        } else {
            $price_html = 'GRATIS';
            $price_class = 'gdos-price-free';
        }

        $title = $method->label;

        ob_start();
?>
        <div class="gdos-shipping-card">
            <div class="gdos-radio-col">
                <div class="gdos-custom-radio"></div>
            </div>
            <div class="gdos-content-col">
                <span class="gdos-title"><?php echo esc_html($title); ?></span>

                <div class="gdos-price-wrapper">
                    <span class="<?php echo esc_attr($price_class); ?>">
                        <?php echo $price_html; ?>
                    </span>
                </div>

                <?php if (!empty($desc)): ?>
                    <span class="gdos-description"><?php echo esc_html($desc); ?></span>
                <?php endif; ?>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    public function totals($input)
    {
        $chosen = \WC()->session->get('chosen_shipping_methods');
        if (empty($chosen)) return $input;

        $method_id = $chosen[0];

        if (strpos($method_id, 'gdos_v2_terminal') !== false) {
            return '<span class="gdos-collect-on-delivery">Pagas al recibir</span>';
        }

        $free_ids = ['gdos_v2_flash', 'gdos_v2_express', 'gdos_v2_pickup'];

        foreach ($free_ids as $id) {
            if (strpos($method_id, $id) !== false && \WC()->cart->shipping_total <= 0) {
                return '<span class="gdos-free-text-total">GRATIS</span>';
            }
        }

        return $input;
    }

    public function scripts()
    {
        if (!is_checkout()) return;
        $key = get_option('gdos_global_api_key', '');
        if (empty($key)) return;

        $del = ['woodmart-google-map-api', 'wd-google-map-api', 'funnelkit-checkout-google-places', 'wfacp-google-address-autocomplete', 'fc-google-address-autocomplete', 'funnelkit-google-maps', 'google-map-api', 'google-maps'];
        foreach ($del as $h) {
            wp_dequeue_script($h);
            wp_deregister_script($h);
        }

        wp_enqueue_script('gdos-google-maps-api', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($key) . '&libraries=places&language=es', [], null, true);

        $root = dirname(__DIR__) . '/module.php';
        $css = Assets::get('assets/checkout-map.css', $root);
        wp_enqueue_style('gdos-checkout-map', $css['url'], [], time());

        $js = Assets::get('assets/checkout-map.js', $root);
        wp_enqueue_script('gdos-checkout-map', $js['url'], ['jquery', 'gdos-google-maps-api'], $js['ver'], true);
    }
}
