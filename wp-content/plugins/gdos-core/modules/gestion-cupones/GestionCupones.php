<?php
// REFACTORIZADO: 2025-12-06 (FINAL: Fix Lista Cupones + Fix Categorías + Fix Fechas)
// /modules/gestion-cupones/GestionCupones.php

namespace GDOS\Modules\GestionCupones;

use GDOS\Core\ModuleInterface;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class GestionCupones implements ModuleInterface
{

    const BRAND_TAXONOMY = 'pa_marca';
    const NONCE_ACTION   = 'gdos_coupon_security';

    public function boot(): void
    {

        // Admin
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_submenu_page'], 30);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 20);
            add_action('wp_ajax_gdos_search_terms', [$this, 'ajax_search_terms']);
            add_filter('manage_edit-shop_coupon_columns', [$this, 'add_stats_column']);
            add_action('manage_shop_coupon_posts_custom_column', [$this, 'render_stats_column'], 10, 2);
        }

        // Frontend / Ajax
        if (! is_admin() || defined('DOING_AJAX')) {
            add_filter('woocommerce_coupon_is_valid', [$this, 'prevent_coupon_stacking'], 10, 2);
            add_filter('woocommerce_coupon_error', [$this, 'custom_coupon_error_message'], 10, 3);
            add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon_start_date'], 20, 2);
            add_filter('woocommerce_coupon_is_valid_for_product', [$this, 'validate_product_brands'], 10, 4);

            add_action('woocommerce_before_calculate_totals', [$this, 'reset_coupon_caps'], 1);
            add_filter('woocommerce_coupon_get_discount_amount', [$this, 'apply_discount_cap'], 10, 5);
            add_action('woocommerce_before_calculate_totals', [$this, 'reset_coupon_caps_hit_flag'], 2);
            add_filter('woocommerce_coupon_get_discount_amount', [$this, 'track_cap_hit'], 20, 5);
            add_filter('woocommerce_cart_totals_coupon_label', [$this, 'display_cap_reached_message'], 10, 2);

            add_action('wp_head', [$this, 'inject_checkout_styles']);
        }
    }

    public function add_admin_submenu_page()
    {
        add_submenu_page(
            'grupodos-main',
            'Cupones Grupo Dos',
            'Cupones',
            'manage_options',
            'gdos-coupons',
            [$this, 'render_main_page'],
            40
        );
    }

    public function enqueue_admin_assets($hook)
    {
        if (! isset($_GET['page']) || $_GET['page'] !== 'gdos-coupons') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_style('woocommerce_admin_styles');
        wp_enqueue_script('selectWoo');
        wp_enqueue_style('select2');
        wp_enqueue_script('wc-admin-meta-boxes');
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_script('woocommerce_admin');

        $ajax_nonce = wp_create_nonce(self::NONCE_ACTION);

        $custom_js = "
        jQuery(function($){
          $('.gdos-term-search').each(function(){
            var \$el = $(this); 
            var taxonomy = \$el.data('taxonomy');
            \$el.selectWoo({
              placeholder: \$el.data('placeholder') || 'Buscar…', 
              allowClear: true, 
              minimumInputLength: 2,
              ajax: {
                url: ajaxurl, 
                dataType: 'json', 
                delay: 250,
                data: function (params) { 
                    return { 
                        action: 'gdos_search_terms', 
                        taxonomy: taxonomy, 
                        q: params.term || '',
                        _ajax_nonce: '{$ajax_nonce}'
                    }; 
                },
                processResults: function (data) { return { results: data }; },
                cache: true
              },
              width: '50%'
            });
          });
          $(document.body).trigger('wc-enhanced-select-init');
        });";

        wp_add_inline_script('wc-enhanced-select', $custom_js);
    }

    public function ajax_search_terms()
    {
        check_ajax_referer(self::NONCE_ACTION);

        if (! current_user_can('manage_woocommerce')) {
            wp_send_json([]);
        }

        $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field(wp_unslash($_GET['taxonomy'])) : '';
        $q        = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

        if (! $taxonomy) wp_send_json([]);

        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 20,
            'search'     => $q
        ]);

        if (is_wp_error($terms)) wp_send_json([]);

        $results = array_map(function ($t) {
            return ['id' => $t->slug, 'text' => $t->name . ' (' . $t->slug . ')'];
        }, $terms);

        wp_send_json($results);
    }

    public function render_main_page()
    {
        if (! current_user_can('manage_options')) return;

        echo '<div class="wrap">';
        echo '<h1>🎟️ Cupones Grupo Dos</h1>';
        echo '<p>' . esc_html__('Administra, crea y analiza cupones de manera centralizada.', 'gdos-core') . '</p>';

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

        if ($action === 'delete') {
            $this->handle_delete_action();
        }

        if (isset($_POST['gdos_coupon_nonce']) && wp_verify_nonce($_POST['gdos_coupon_nonce'], 'gdos_save_coupon')) {
            $this->handle_save_action();
        }

        if ($action === 'stats') {
            require_once __DIR__ . '/views/stats-view.php';
        } elseif ($action === 'new' || $action === 'edit') {
            require_once __DIR__ . '/views/form-view.php';
        } else {
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=gdos-coupons&action=new')) . '" class="button button-primary">+ Crear nuevo cupón</a></p>';

            // CORRECCIÓN AQUÍ: Agregada la consulta que faltaba
            $coupons = get_posts([
                'post_type'      => 'shop_coupon',
                'posts_per_page' => 50,
                'post_status'    => 'any' // 'any' permite ver también borradores o programados
            ]);

            require_once __DIR__ . '/views/list-view.php';
        }
        echo '</div>';
    }

    private function handle_delete_action()
    {
        $del_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $nonce  = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';

        if ($del_id && wp_verify_nonce($nonce, 'gdos_delete_coupon_' . $del_id) && current_user_can('delete_post', $del_id)) {
            $res = wp_trash_post($del_id);
            $msg = $res ? '🗑️ Cupón enviado a la papelera.' : 'No se pudo eliminar.';
            $cls = $res ? 'success' : 'error';
            echo '<div class="notice notice-' . $cls . '"><p>' . esc_html($msg) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Acción no autorizada.</p></div>';
        }
    }

    private function handle_save_action()
    {
        $res = $this->save_coupon();
        if (is_wp_error($res)) {
            echo '<div class="notice notice-error"><p>❌ ' . esc_html($res->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>✅ Cupón guardado.</p></div>';
        }
    }

    private function save_coupon()
    {
        if (! function_exists('wc_get_order_statuses')) {
            return new WP_Error('no-wc', 'WooCommerce no está disponible.');
        }

        $edit_id = isset($_POST['edit_id']) ? absint($_POST['edit_id']) : 0;
        $code    = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';

        if (! $code) return new WP_Error('no-code', 'El código del cupón es obligatorio.');

        if ($edit_id) {
            $coupon_id = $edit_id;
            wp_update_post(['ID' => $coupon_id, 'post_title' => $code, 'post_type' => 'shop_coupon']);
        } else {
            if (get_page_by_title($code, OBJECT, 'shop_coupon')) return new WP_Error('dup', 'Ya existe un cupón con ese código.');
            $coupon_id = wp_insert_post(['post_title' => $code, 'post_status' => 'publish', 'post_type' => 'shop_coupon']);
        }

        if (is_wp_error($coupon_id) || ! $coupon_id) return new WP_Error('save-fail', 'No se pudo guardar el cupón.');

        $sanitize_ids = function ($val) {
            return ! empty($val) ? implode(',', array_map('absint', (array) $val)) : '';
        };
        $sanitize_slugs = function ($val) {
            return ! empty($val) ? implode(',', array_map('sanitize_title', (array) $val)) : '';
        };

        $inc_products   = isset($_POST['include_products_ids']) ? $sanitize_ids($_POST['include_products_ids']) : sanitize_text_field($_POST['include_products'] ?? '');
        $exc_products   = isset($_POST['exclude_products_ids']) ? $sanitize_ids($_POST['exclude_products_ids']) : sanitize_text_field($_POST['exclude_products'] ?? '');

        $inc_cats_slugs = isset($_POST['include_categories_slugs']) ? $sanitize_slugs($_POST['include_categories_slugs']) : sanitize_text_field($_POST['include_categories'] ?? '');
        $exc_cats_slugs = isset($_POST['exclude_categories_slugs']) ? $sanitize_slugs($_POST['exclude_categories_slugs']) : sanitize_text_field($_POST['exclude_categories'] ?? '');

        $inc_brands     = isset($_POST['include_brands_slugs']) ? $sanitize_slugs($_POST['include_brands_slugs']) : sanitize_text_field($_POST['include_brands'] ?? '');
        $exc_brands     = isset($_POST['exclude_brands_slugs']) ? $sanitize_slugs($_POST['exclude_brands_slugs']) : sanitize_text_field($_POST['exclude_brands'] ?? '');

        $email_rest_str = sanitize_text_field($_POST['email_restriction'] ?? '');

        delete_transient('gdos_ids_pcat_' . md5($inc_cats_slugs));
        delete_transient('gdos_ids_pcat_' . md5($exc_cats_slugs));

        $metas = [
            'discount_type'               => sanitize_text_field($_POST['discount_type'] ?? 'percent'),
            'coupon_amount'               => wc_format_decimal($_POST['amount'] ?? 0),
            'individual_use'              => 'no',
            'product_ids'                 => $inc_products,
            'exclude_product_ids'         => $exc_products,
            'product_categories'          => $this->term_slugs_to_ids($inc_cats_slugs, 'product_cat'),
            'exclude_product_categories'  => $this->term_slugs_to_ids($exc_cats_slugs, 'product_cat'), // Corregido: Key nativa
            'customer_email'              => array_filter(array_map('sanitize_email', array_map('trim', explode(',', $email_rest_str)))),
            'usage_limit'                 => isset($_POST['usage_limit']) ? absint($_POST['usage_limit']) : '',
            'usage_limit_per_user'        => isset($_POST['usage_limit_per_user']) ? absint($_POST['usage_limit_per_user']) : '',
            'minimum_amount'              => wc_format_decimal($_POST['min_spend'] ?? ''),
            '_gdos_date_from'             => sanitize_text_field($_POST['date_start'] ?? ''),
            '_gdos_max_discount'          => wc_format_decimal($_POST['max_discount'] ?? ''),
            '_gdos_include_brands'        => $inc_brands,
            '_gdos_exclude_brands'        => $exc_brands
        ];

        foreach ($metas as $key => $value) {
            if ($value !== '') update_post_meta($coupon_id, $key, $value);
            else delete_post_meta($coupon_id, $key);
        }

        $date_end = sanitize_text_field($_POST['date_end'] ?? '');
        if ($date_end) {
            $dt_end = date_create($date_end . ' 23:59:59', wp_timezone());
            if ($dt_end) update_post_meta($coupon_id, 'date_expires', $dt_end->getTimestamp());
            else update_post_meta($coupon_id, 'date_expires', strtotime($date_end . ' 23:59:59'));
        } else {
            delete_post_meta($coupon_id, 'date_expires');
        }

        return $coupon_id;
    }

    public function add_stats_column($cols)
    {
        $cols['gdos_stats'] = 'Estadísticas';
        return $cols;
    }

    public function render_stats_column($col, $post_id)
    {
        if ($col !== 'gdos_stats') return;
        $code = get_the_title($post_id);
        $url  = admin_url('admin.php?page=gdos-coupons&action=stats&code=' . urlencode($code));
        echo '<a class="button button-small" href="' . esc_url($url) . '">Ver</a>';
    }

    // FRONTEND
    public function prevent_coupon_stacking($valid, $coupon)
    {
        if (is_wp_error($valid) || $valid === false) return $valid;
        if (! function_exists('WC') || ! WC()->cart) return $valid;

        $applied = (array) WC()->cart->get_applied_coupons();
        if (empty($applied)) return $valid;

        $current_code = strtolower($coupon->get_code());
        $already_has  = in_array($current_code, array_map('strtolower', $applied), true);

        if (! $already_has) return false;
        return $valid;
    }

    public function custom_coupon_error_message($err, $err_code, $coupon)
    {
        if ((int) $err_code === 100 && ! empty(WC()->cart->get_applied_coupons())) {
            $applied = array_map('strtolower', WC()->cart->get_applied_coupons());
            $code    = strtolower($coupon->get_code());

            if (! in_array($code, $applied)) {
                return __('⚠️ Solo es posible utilizar un cupón por compra. Ya tienes uno aplicado.', 'gdos-core');
            }
        }
        return $err;
    }

    public function validate_coupon_start_date($valid, $coupon)
    {
        if (is_wp_error($valid) || $valid === false) return $valid;

        $start_date_str = $coupon->get_meta('_gdos_date_from');
        if (empty($start_date_str)) return $valid;

        // CORREGIDO: Timestamp comparison
        $start_ts = strtotime($start_date_str . ' 00:00:00');
        $now_ts   = current_time('timestamp');

        if ($now_ts < $start_ts) {
            $date_display = date_i18n(get_option('date_format'), $start_ts);
            return new WP_Error('coupon_not_started', sprintf(__('Este cupón estará disponible a partir del %s.', 'gdos-core'), $date_display));
        }
        return $valid;
    }

    public function validate_product_brands($valid, $product, $coupon)
    {
        $inc = trim((string) $coupon->get_meta('_gdos_include_brands'));
        $exc = trim((string) $coupon->get_meta('_gdos_exclude_brands'));
        if ($inc === '' && $exc === '') return $valid;

        $pid = $product->get_id();
        $cache_key = 'gdos_prod_brands_' . $pid;
        $brand_slugs = wp_cache_get($cache_key);

        if (false === $brand_slugs) {
            $brand_slugs = wp_get_post_terms($pid, self::BRAND_TAXONOMY, ['fields' => 'slugs']);
            if (! is_array($brand_slugs)) $brand_slugs = [];
            wp_cache_set($cache_key, $brand_slugs, '', 60);
        }

        $str_to_arr = function ($str) {
            return array_filter(array_map('trim', explode(',', $str)));
        };
        $inc_ok = ($inc === '') ? true : (count(array_intersect($brand_slugs, $str_to_arr($inc))) > 0);
        $exc_ok = ($exc === '') ? true : (count(array_intersect($brand_slugs, $str_to_arr($exc))) === 0);

        return ($inc_ok && $exc_ok) ? $valid : false;
    }

    public function reset_coupon_caps($cart)
    {
        if (! is_admin() || defined('DOING_AJAX')) $cart->gdos_coupon_caps = [];
    }

    public function reset_coupon_caps_hit_flag($cart)
    {
        if (! is_admin() || defined('DOING_AJAX')) $cart->gdos_coupon_caps_hit = [];
    }

    public function apply_discount_cap($discount, $discounting_amount, $cart_item, $single, $coupon)
    {
        if (! isset(WC()->cart->gdos_coupon_caps)) WC()->cart->gdos_coupon_caps = [];

        $cid = $coupon->get_id();
        $cap = $coupon->get_meta('_gdos_max_discount');
        if ($cap === '') return $discount;

        $used      = WC()->cart->gdos_coupon_caps[$cid] ?? 0.0;
        $remaining = max(0, (float) $cap - (float) $used);
        $allowed   = min((float) $discount, $remaining);
        WC()->cart->gdos_coupon_caps[$cid] = $used + $allowed;

        return $allowed;
    }

    public function track_cap_hit($discount, $discounting_amount, $cart_item, $single, $coupon)
    {
        $cid = $coupon->get_id();
        $cap = $coupon->get_meta('_gdos_max_discount');
        if ($cap === '') return $discount;

        if (isset(WC()->cart->gdos_coupon_caps[$cid])) {
            $used = (float) WC()->cart->gdos_coupon_caps[$cid];
            if ($used >= ((float) $cap - 0.0001)) WC()->cart->gdos_coupon_caps_hit[$cid] = true;
        }
        return $discount;
    }

    public function display_cap_reached_message($label, $coupon)
    {
        $cid = $coupon->get_id();
        if (isset(WC()->cart->gdos_coupon_caps_hit[$cid]) && WC()->cart->gdos_coupon_caps_hit[$cid]) {
            $cap        = $coupon->get_meta('_gdos_max_discount');
            $price_html = wp_kses_post(wc_price((float) $cap));
            $label .= ' <span class="gdos-cap-reached" style="color:#666; font-size:11px; display:block;">' . sprintf(__('Límite de reintegro alcanzado (%s)', 'gdos-core'), $price_html) . '</span>';
        }
        return $label;
    }

    private function term_slugs_to_ids($slugs_csv, $taxonomy)
    {
        $slugs = array_filter(array_map('trim', explode(',', (string) $slugs_csv)));
        if (empty($slugs)) return [];

        $cache_key = 'gdos_ids_' . $taxonomy . '_' . md5(implode(',', $slugs));
        $ids = get_transient($cache_key);

        if (false === $ids) {
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'slug' => $slugs]);
            $ids = (is_wp_error($terms) || empty($terms)) ? [] : array_map(function ($t) {
                return (int) $t->term_id;
            }, $terms);
            set_transient($cache_key, $ids, HOUR_IN_SECONDS);
        }
        return $ids;
    }

    public function term_ids_to_slugs($ids, $taxonomy)
    {
        $ids = array_filter(array_map('absint', (array) $ids));
        if (empty($ids)) return '';
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'include' => $ids]);
        if (is_wp_error($terms) || empty($terms)) return '';
        return implode(',', array_map(function ($t) {
            return $t->slug;
        }, $terms));
    }

    public function stat_card($title, $value)
    {
        return '<div style="flex:1 1 220px; background:#fff; border:1px solid #e3e3e3; border-radius:10px; padding:14px 16px; box-shadow:0 1px 2px rgba(0,0,0,.03); min-width:220px;"><div style="color:#666; font-size:12px; text-transform:uppercase; letter-spacing:.4px;">' . esc_html($title) . '</div><div style="font-size:20px; font-weight:700; margin-top:6px;">' . esc_html($value) . '</div></div>';
    }

    public function inject_checkout_styles()
    {
        if (! is_cart() && ! is_checkout()) return;
?>
        <style>
            .woocommerce-error {
                border-top: none !important;
                background-color: #fff !important;
                color: #333 !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                border-left: 4px solid #d9534f !important;
                border-radius: 6px;
                padding: 16px 20px !important;
                font-size: 14px;
                display: flex;
                align-items: center;
                margin-bottom: 20px !important
            }

            .woocommerce-error::before,
            .woocommerce-error::after {
                display: none !important
            }

            .woocommerce-error li {
                margin: 0 !important;
                list-style: none !important;
                padding: 0 !important
            }
        </style>
<?php
    }
}
