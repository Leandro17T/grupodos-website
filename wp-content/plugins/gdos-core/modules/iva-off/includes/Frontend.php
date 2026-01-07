<?php
namespace GDOS\Modules\IvaOff\includes;
use GDOS\Modules\IvaOff\IvaOff;
use GDOS\Modules\IvaOff\includes\Admin;
use GDOS\Core\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {
    private static bool $topbar_printed = false;

    public function __construct() {
        // 🔥 BLOQUEO DE CACHÉ NUCLEAR
        add_action('wp', [$this, 'force_nuclear_nocache_on_checkout']);
        add_action('template_redirect', [$this, 'send_nocache_headers_for_checkout'], 0);

        // Evitar cache de tarifas de envío
        add_filter('woocommerce_shipping_transient_version', [$this, 'bust_shipping_transient_version']);

        // Descuento / mensajes / badge
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_discount']);
        add_filter('woocommerce_gateway_description', [$this, 'add_message_to_gateway'], 10, 2);
        add_filter('woocommerce_gateway_title', [$this, 'add_badge_to_gateway'], 10, 2);

        // Checkout AJAX
        add_action('woocommerce_checkout_update_order_review', [$this, 'sync_payment_method_and_recalc']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Banner hooks
        add_action('woodmart_after_header', [$this, 'render_top_bar'], 5);
        add_action('woocommerce_before_main_content', [$this, 'render_top_bar'], 1);

        // Shortcode ESI
        add_shortcode('gdos_ivaoff_esi_topbar', [$this, 'esi_topbar_shortcode']);

        // Filtros de cupones
        add_filter('woocommerce_coupons_enabled', [$this, 'maybe_disable_coupons']);
        add_filter('woocommerce_coupon_is_valid', [$this, 'maybe_block_coupon_application'], 10, 2);
    }

    public function force_nuclear_nocache_on_checkout() {
        if (function_exists('is_checkout') && (is_checkout() || is_cart())) {
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
            if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);
            if (!defined('DONOTMINIFY')) define('DONOTMINIFY', true);
            if (!defined('LSCACHE_NO_CACHE')) define('LSCACHE_NO_CACHE', true);
        }
    }

    public function send_nocache_headers_for_checkout() {
        if (function_exists('is_checkout') && (is_checkout() || is_cart())) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-LiteSpeed-Cache-Control: no-cache');
            if (has_action('litespeed_control_set_nocache')) {
                do_action('litespeed_control_set_nocache');
            }
        }
    }

    private function get_excluded_cat_slugs() : array {
        return (array) apply_filters('gdos_ivaoff_excluded_cats', ['electrodomesticos', 'tv-y-audio']);
    }

    private function is_excluded_product(int $product_id) : bool {
        $slugs = $this->get_excluded_cat_slugs();
        return !empty($slugs) && has_term($slugs, 'product_cat', $product_id);
    }

    public function bust_shipping_transient_version($version) {
        return uniqid('gdos_ship_', true);
    }

    private function get_options() {
        return wp_parse_args(get_option(Admin::OPT_KEY, []), Admin::get_defaults());
    }

    public function render_top_bar() {
        if (self::$topbar_printed) return;
        if (!function_exists('is_product') || !is_product()) return;
        $o = $this->get_options();
        if (empty($o['topbar_enabled']) || !IvaOff::is_active_now()) return;
        $product = wc_get_product(get_queried_object_id());
        if (!$product || $this->is_excluded_product($product->get_id())) return;

        if (shortcode_exists('esi')) {
            $pid = get_queried_object_id();
            echo do_shortcode('[esi include="gdos_ivaoff_esi_topbar" pid="'.$pid.'" cache="off" ttl="0"]');
        } else {
            echo $this->build_topbar_markup(get_queried_object_id());
        }
        self::$topbar_printed = true;
    }

    public function esi_topbar_shortcode($atts) : string {
        $pid = isset($atts['pid']) ? absint($atts['pid']) : 0;
        if (!$pid) $pid = function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
        if (!$pid) return '';
        return $this->build_topbar_markup($pid);
    }

    private function build_topbar_markup($product_id) : string {
        if (!IvaOff::is_active_now()) return '';
        $o = $this->get_options();
        if (empty($o['topbar_enabled'])) return '';
        $product = wc_get_product($product_id);
        if (!$product || $this->is_excluded_product($product->get_id())) return '';

        $rate = (float)$o['rate'];
        $base = (float)wc_get_price_to_display($product);
        if ($base <= 0) return '';

        $precio_fmt = wc_price($base * (1 - $rate));
        $bg = esc_attr($o['topbar_bg']);
        $fg = esc_attr($o['topbar_fg']);
        $styles = sprintf('--topbar-bg:%1$s; --topbar-fg:%2$s; background:%1$s !important; color:%2$s !important;', $bg, $fg);

        $tpl = (string)($o['topbar_text'] ?? '');
        if ($tpl === '') $tpl = 'Precio pagando con <span class="gdos-acento">transferencia bancaria</span> - IVA OFF {rate_percent}%: {precio}';
        
        $price_html = '<b id="gdos-ivaoff-price" class="gdos-ivaoff-price-tag">' . wp_strip_all_tags($precio_fmt) . '</b>';
        
        $replaced = strtr($tpl, [
            '{precio}'       => $price_html,
            '{precio_raw}'   => wp_strip_all_tags($precio_fmt),
            '{rate}'         => (string)$rate,
            '{rate_percent}' => number_format_i18n($rate * 100, 2),
        ]);

        $allowed = ['br'=>[], 'b'=>['id'=>true, 'class'=>true, 'style'=>true], 'strong'=>[], 'em'=>[], 'i'=>[], 'u'=>[], 'span'=>['class'=>true], 'a'=>['href'=>true,'target'=>true,'rel'=>true]];
        $replaced = preg_replace('/<span([^>]*?)class=(["\'])(?!gdos-acento\\b).*?\\2([^>]*)>/i', '<span$1$3>', (string)$replaced);
        $text = wp_kses($replaced, $allowed);

        $css = sprintf('<style id="gdos-ivaoff-inline-fix">#gdos-ivaoff-topbar, #gdos-ivaoff-topbar .gdos-ivaoff-topbar-inner { background:%1$s !important; color:%2$s !important; } #gdos-ivaoff-topbar b, #gdos-ivaoff-topbar strong, #gdos-ivaoff-topbar .gdos-acento, #gdos-ivaoff-topbar a, #gdos-ivaoff-topbar a:link, #gdos-ivaoff-topbar a:visited, #gdos-ivaoff-topbar a:hover, #gdos-ivaoff-topbar a:active, #gdos-ivaoff-topbar svg { color:%2$s !important; fill:%2$s !important; text-decoration:none !important; }</style>', $bg, $fg);
        $icon = '<svg class="gdos-ivaoff-icon" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg"><path d="M2 20h20v2H2v-2zm2-8h2v7H4v-7zm5 0h2v7H9v-7zm5 0h2v7h-2v-7zm5 0h2v7h-2v-7zM2 7l10-5 10 5v4H2V7zm10-2.236L4.472 8H19.53L12 4.764zM22 14v2H2v-2h20z" fill="currentColor"/></svg>';

        return $css . '<div id="gdos-ivaoff-topbar" class="gdos-ivaoff-topbar" data-gdos-rate="' . esc_attr($rate) . '" style="' . $styles . '"><div class="gdos-ivaoff-topbar-inner">' . $icon . '<div class="gdos-ivaoff-content">' . $text . '</div></div></div>';
    }

    public function enqueue_assets() {
        $needs_assets = (is_checkout() || is_cart() || (function_exists('is_product') && is_product()));
        if (!$needs_assets) return;

        // Rutas relativas calculadas desde el archivo principal del módulo para que Assets encuentre 'assets/...'
        $module_root = dirname(__DIR__) . '/module.php';
        
        $css = Assets::get('assets/css/frontend.css', $module_root);
        wp_enqueue_style('gdos-ivaoff-frontend', $css['url'], [], $css['ver']);

        if ((function_exists('is_product') && is_product()) || (function_exists('is_checkout') && (is_checkout() || is_cart()))) {
            $js = Assets::get('assets/js/frontend.js', $module_root);
            wp_enqueue_script('gdos-ivaoff-frontend', $js['url'], ['jquery'], $js['ver'], true);
            
            $o = $this->get_options();
            wp_localize_script('gdos-ivaoff-frontend', 'gdosIvaOff', [
                'active' => IvaOff::is_active_now() ? 1 : 0,
                'coupon_notice_enabled' => isset($o['coupon_notice_enabled']) ? $o['coupon_notice_enabled'] : 1,
                'coupon_notice_text' => isset($o['coupon_notice_text']) ? $o['coupon_notice_text'] : __('Si pagás con transferencia, el beneficio IVA OFF reemplaza cualquier cupón.', 'gdos')
            ]);
        }
    }

    public function apply_discount($cart) {
        if (is_admin() && !wp_doing_ajax() || !IvaOff::is_active_now()) return;
        if ((\WC()->session->get('chosen_payment_method') ?? '') !== 'bacs' || $cart->has_discount()) return;
        $base = 0.0;
        foreach ($cart->get_cart() as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            if (!$pid || $this->is_excluded_product($pid)) continue;
            $base += (float)($item['line_subtotal'] ?? 0);
        }
        if ($base <= 0) return;
        $rate = (float)$this->get_options()['rate'];
        if ($rate > 0) $cart->add_fee(sprintf('IVA OFF (-%s%%)', number_format_i18n($rate*100,2)), -1 * round($base * $rate, wc_get_price_decimals()));
    }

    public function add_message_to_gateway($description, $gateway_id) {
        if ($gateway_id !== 'bacs' || !IvaOff::is_active_now()) return $description;
        return $description . '<div class="gdos-ivaoff-note">' . wp_kses_post($this->get_options()['message']) . '</div>';
    }

    public function add_badge_to_gateway($title, $gateway_id) {
        if (is_admin() || $gateway_id !== 'bacs' || !IvaOff::is_active_now()) return $title;
        $o = $this->get_options();
        return empty($o['badge_enabled']) ? $title : $title . ' <span class="gdos-ivaoff-badge" style="background:'.esc_attr($o['badge_bg']).';color:'.esc_attr($o['badge_fg']).';">'.esc_html($o['badge_text']).'</span>';
    }

    public function sync_payment_method_and_recalc($post_data_string) {
        parse_str((string)$post_data_string, $data);
        if (!empty($data['payment_method']) && \WC()->session) \WC()->session->set('chosen_payment_method', sanitize_text_field($data['payment_method']));
        if (\WC()->session) {
            $sess = \WC()->session->get_session_data();
            foreach ((array)$sess as $k => $v) {
                if (strpos($k, 'shipping_for_package_') === 0) \WC()->session->__unset($k);
            }
        }
        if (IvaOff::is_active_now() && isset($data['payment_method']) && $data['payment_method'] === 'bacs' && function_exists('WC') && \WC()->cart) {
            $applied = (array)\WC()->cart->get_applied_coupons();
            if (!empty($applied)) {
                foreach ($applied as $code) \WC()->cart->remove_coupon($code);
                if (function_exists('wc_add_notice')) wc_add_notice(__('Los cupones no se aplican pagando por transferencia debido al beneficio IVA OFF.'), 'notice');
            }
        }
        if (function_exists('WC')) {
            \WC()->shipping()->reset_shipping();
            if (\WC()->cart) {
                \WC()->cart->calculate_shipping();
                \WC()->cart->calculate_totals();
            }
        }
    }

    public function maybe_disable_coupons($enabled) {
        if (!IvaOff::is_active_now()) return $enabled;
        $chosen = \WC()->session ? (\WC()->session->get('chosen_payment_method') ?? '') : '';
        return ($chosen === 'bacs') ? false : $enabled;
    }

    public function maybe_block_coupon_application($valid, $coupon) {
        if (!$valid || !IvaOff::is_active_now()) return $valid;
        $chosen = \WC()->session ? (\WC()->session->get('chosen_payment_method') ?? '') : '';
        if ($chosen === 'bacs') {
            if (function_exists('wc_add_notice')) wc_add_notice(__('Los cupones no se pueden usar pagando por transferencia bancaria, porque ya tenés IVA OFF.'), 'error');
            return false;
        }
        return $valid;
    }
}