<?php
/**
 * GDOS Core – Módulo Combo Builder (Arma tu Combo)
 * Shortcode único: [gdos_combo_builder]
 * Requiere WooCommerce
 */

if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) return;

    class GDOS_Combo_Builder {
        const OPTION_KEY = 'gdos_combo_config';
        const NONCE_KEY  = 'gdos_combo_nonce';
        const SLUG       = 'gdos-combo-builder';

        public function __construct() {
            // Admin
            add_action('admin_menu', [$this, 'register_admin_page']);
            add_action('admin_init', [$this, 'register_settings']);

            // Assets
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
            add_action('wp_enqueue_scripts',    [$this, 'enqueue_public']);

            // Shortcode
            add_shortcode('gdos_combo_builder', [$this, 'render_shortcode']);

            // AJAX Add to cart
            add_action('wp_ajax_gdos_combo_add_to_cart',        [$this, 'ajax_add_to_cart']);
            add_action('wp_ajax_nopriv_gdos_combo_add_to_cart', [$this, 'ajax_add_to_cart']);
        }

        /* ===========================
         * Admin
         * =========================== */
        public function register_admin_page() {
    // Menú propio (nivel superior) para evitar depender del slug del Core.
    add_menu_page(
        'Combo Builder',
        'Combo Builder',
        'manage_woocommerce',
        self::SLUG,
        [$this, 'admin_page_html'],
        'dashicons-screenoptions',
        57
    );
}


        public function register_settings() {
            register_setting(self::SLUG, self::OPTION_KEY, [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize_config'],
                'default'           => [
                    'title' => 'Arma tu Combo',
                    'cta_text' => 'Finalizar pedido',
                    'steps' => []
                ],
            ]);
        }

        public function sanitize_config($input) {
            $out = [
                'title'   => isset($input['title']) ? sanitize_text_field($input['title']) : 'Arma tu Combo',
                'cta_text'=> isset($input['cta_text']) ? sanitize_text_field($input['cta_text']) : 'Finalizar pedido',
                'steps'   => []
            ];

            if (!empty($input['steps']) && is_array($input['steps'])) {
                foreach ($input['steps'] as $step) {
                    $title = isset($step['title']) ? sanitize_text_field($step['title']) : '';
                    $products_raw = isset($step['products']) ? sanitize_text_field($step['products']) : '';
                    // Normalizamos IDs: coma separada -> array integers únicos
                    $ids = array_filter(array_unique(array_map(function($v){
                        $v = trim($v);
                        return ctype_digit($v) ? intval($v) : null;
                    }, explode(',', $products_raw))));

                    $required = !empty($step['required']) ? 1 : 0;
                    $allow_qty = !empty($step['allow_qty']) ? 1 : 0;

                    if ($title && !empty($ids)) {
                        $out['steps'][] = [
                            'title'      => $title,
                            'products'   => implode(',', $ids),
                            'required'   => $required,
                            'allow_qty'  => $allow_qty,
                        ];
                    }
                }
            }
            return $out;
        }

        public function admin_page_html() {
            $cfg = get_option(self::OPTION_KEY);
            if (!is_array($cfg)) $cfg = [];
            include __DIR__ . '/views/admin-page.php';
        }

        public function enqueue_admin($hook) {
            if (strpos($hook, self::SLUG) === false) return;
            wp_enqueue_style(self::SLUG.'-admin', plugins_url('assets/admin.css', __FILE__), [], '1.0.0');
            wp_enqueue_script(self::SLUG.'-admin', plugins_url('assets/admin.js', __FILE__), ['jquery'], '1.0.0', true);
        }

        /* ===========================
         * Public / Shortcode
         * =========================== */
        public function enqueue_public() {
            // Solo en páginas con shortcode
            if (!is_singular() && !is_front_page() && !is_page()) return;
            // Podrías hacer una detección más fina si prefieres

            wp_register_style(self::SLUG.'-public', plugins_url('assets/public.css', __FILE__), [], '1.0.0');
            wp_register_script(self::SLUG.'-public', plugins_url('assets/public.js', __FILE__), ['jquery'], '1.1.0', true);

            wp_localize_script(self::SLUG.'-public', 'GDOS_COMBO', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce(self::NONCE_KEY),
                'i18n'     => [
                    'next'     => __('Siguiente', 'gdos'),
                    'prev'     => __('Anterior', 'gdos'),
                    'required' => __('Este paso es obligatorio', 'gdos'),
                    'added'    => __('Productos añadidos al carrito', 'gdos'),
                    'error'    => __('Ocurrió un error. Intenta nuevamente.', 'gdos'),
                ],
            ]);
        }

        public function render_shortcode($atts) {
            $cfg = get_option(self::OPTION_KEY);
            if (empty($cfg) || empty($cfg['steps'])) {
                return '<div class="gdos2-alert gdos2-alert-info">An no has configurado los pasos del combo.</div>';
            }

            // Preparamos datos de productos para render/JS
            $steps = [];
            foreach ($cfg['steps'] as $s) {
                $ids = array_filter(array_map('intval', explode(',', $s['products'] ?? '')));
                $products = [];
                foreach ($ids as $pid) {
                    $p = wc_get_product($pid);
                    if (!$p) continue;
                    $img = wp_get_attachment_image_src($p->get_image_id(), 'woocommerce_thumbnail');
                    $products[] = [
                        'id'    => $pid,
                        'name'  => $p->get_name(),
                        'price' => (float) wc_get_price_to_display($p),
                        'price_html' => $p->get_price_html(),
                        'image' => $img ? $img[0] : wc_placeholder_img_src(),
                        'in_stock' => $p->is_in_stock() ? 1 : 0,
                    ];
                }
                if (!empty($products)) {
                    $steps[] = [
                        'title'      => $s['title'],
                        'required'   => !empty($s['required']) ? 1 : 0,
                        'allow_qty'  => !empty($s['allow_qty']) ? 1 : 0,
                        'products'   => $products,
                    ];
                }
            }

            wp_enqueue_style(self::SLUG.'-public');
            wp_enqueue_script(self::SLUG.'-public');

            ob_start(); ?>
            <div class="gdos-combo-root" data-config='<?php echo esc_attr(wp_json_encode([
                'title'   => $cfg['title'] ?? 'Arma tu Combo',
                'ctaText' => $cfg['cta_text'] ?? 'Finalizar pedido',
                'steps'   => $steps
            ], JSON_UNESCAPED_UNICODE)); ?>'>
                <div class="gdos-combo-header">
                    <h2 class="gdos-combo-title"><?php echo esc_html($cfg['title'] ?? 'Arma tu Combo'); ?></h2>
                </div>

                <div class="gdos-combo-layout">
                    <!-- Paso a paso -->
                    <div class="gdos-combo-steps">
                        <div class="gdos-combo-stepper"></div>
                        <div class="gdos-combo-content"></div>
                        <div class="gdos-combo-nav">
                            <button class="gdos-btn gdos-prev" type="button" disabled><?php esc_html_e('Anterior','gdos'); ?></button>
                            <button class="gdos-btn gdos-next" type="button"><?php esc_html_e('Siguiente','gdos'); ?></button>
                            <button class="gdos-btn gdos-finish" type="button" disabled>
                                <?php echo esc_html($cfg['cta_text'] ?? 'Finalizar pedido'); ?>
                            </button>
                        </div>
                        <div class="gdos-combo-msg"></div>
                    </div>

                    <!-- Resumen flotante -->
                    <aside class="gdos-combo-summary">
                        <div class="gdos-summary-card">
                            <h3><?php esc_html_e('Resumen del combo','gdos'); ?></h3>
                            <div class="gdos-summary-items"></div>
                            <div class="gdos-summary-total">
                                <span><?php esc_html_e('Total','gdos'); ?>:</span>
                                <strong class="gdos-total-amount">-</strong>
                            </div>
                            <p class="gdos-summary-note">
                                <?php esc_html_e('El botón finalizar se habilita cuando completes los pasos obligatorios.','gdos'); ?>
                            </p>
                        </div>
                    </aside>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        /* ===========================
         * AJAX: agregar al carrito
         * =========================== */
        public function ajax_add_to_cart() {
            check_ajax_referer(self::NONCE_KEY, 'nonce');

            $payload = isset($_POST['items']) ? (array) $_POST['items'] : [];
            if (empty($payload)) {
                wp_send_json_error(['message' => 'No hay items para agregar.']);
            }

            $added = [];
            foreach ($payload as $it) {
                $pid = isset($it['id']) ? intval($it['id']) : 0;
                $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                if ($pid <= 0 || $qty <= 0) continue;

                $product = wc_get_product($pid);
                if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
                    continue;
                }

                $cart_key = WC()->cart->add_to_cart($pid, $qty);
                if ($cart_key) $added[] = $cart_key;
            }

            if (!empty($added)) {
                wp_send_json_success(['message' => 'Agregado', 'redirect' => wc_get_cart_url()]);
            } else {
                wp_send_json_error(['message' => 'No se pudo agregar al carrito.']);
            }
        }
    }

    new GDOS_Combo_Builder();
});
