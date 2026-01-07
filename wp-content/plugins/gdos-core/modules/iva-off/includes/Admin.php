<?php
namespace GDOS\Modules\IvaOff\includes;
use GDOS\Modules\IvaOff\IvaOff;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {
    const OPT_KEY = 'gdos_ivaoff_options';
    const PAGE_SLUG = 'gdos-iva-off';
    const NONCE_ACTION = 'gdos_iva_off_save_settings';

    public function __construct() {
        // Prioridad 99 para asegurar que 'grupodos-main' ya existe
        add_action('admin_menu', [$this, 'add_menu'], 99);
        add_action('admin_init', [$this, 'handle_save']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public static function get_defaults(): array {
        return [
            'enabled' => 0,
            'rate' => '0.1803',
            'message' => '¡Pagando por Transferencia bancaria obtenés IVA OFF (-18,03%)! Válido solo con este medio. No acumulable con cupones.',
            'badge_enabled' => 1,
            'badge_text' => 'IVA OFF',
            'badge_bg' => '#F3F4F6',
            'badge_fg' => '#111827',
            'start_date' => '',
            'end_date' => '',
            'topbar_enabled' => 1,
            'topbar_bg' => '#f8fafc',
            'topbar_fg' => '#1e293b',
            'topbar_text' => 'Precio pagando con <span class="gdos-acento">transferencia bancaria</span> - IVA OFF {rate_percent}%: {precio}',
            'coupon_notice_enabled' => 1,
            'coupon_notice_text' => 'Si pagás con transferencia, el beneficio IVA OFF reemplaza cualquier cupón.',
        ];
    }
    
    public function add_menu() {
        // CORREGIDO: Ahora es un submenú de 'grupodos-main'
        add_submenu_page(
            'grupodos-main',        // Slug del padre (AdminMenuPrincipal)
            'IVA OFF',              // Título de la página
            'IVA OFF',              // Título del menú
            'manage_options',       // Capability
            self::PAGE_SLUG,        // Slug de esta página
            [$this, 'render_page']  // Función que pinta el contenido
        );
    }

    public function enqueue_assets($hook) {
        // Verifica si estamos en la página correcta (sea padre o hijo)
        if (strpos($hook, self::PAGE_SLUG) === false) return;
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public function handle_save() {
        if (isset($_POST['submit']) && isset($_POST['_wpnonce'])) {
            if (!wp_verify_nonce(sanitize_key($_POST['_wpnonce']), self::NONCE_ACTION)) {
                wp_die('Error de seguridad (nonce inválido).');
            }
            if (!current_user_can('manage_options')) {
                wp_die('No tienes permisos para guardar estos ajustes.');
            }

            $d = self::get_defaults(); 
            $in = $_POST[self::OPT_KEY] ?? [];
            $out = [];
            
            $out['enabled'] = !empty($in['enabled']) ? 1 : 0;
            
            foreach (['start_date','end_date'] as $k) {
                $val = isset($in[$k]) ? trim((string)$in[$k]) : '';
                $out[$k] = (preg_match('/^\d{4}-\d{2}-\d{2}$/',$val)) ? $val : '';
            }

            $out['rate'] = (string)max(0,min(1,(float)str_replace(',','.',($in['rate'] ?? $d['rate']))));
            $out['message'] = wp_kses_post($in['message'] ?? $d['message']);
            
            // Badge
            $out['badge_enabled'] = !empty($in['badge_enabled']) ? 1 : 0;
            $out['badge_text'] = sanitize_text_field($in['badge_text'] ?? $d['badge_text']);
            
            $hex_regex = '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/i';
            $out['badge_bg'] = (isset($in['badge_bg']) && preg_match($hex_regex, $in['badge_bg'])) ? $in['badge_bg'] : $d['badge_bg'];
            $out['badge_fg'] = (isset($in['badge_fg']) && preg_match($hex_regex, $in['badge_fg'])) ? $in['badge_fg'] : $d['badge_fg'];
            
            // Topbar
            $out['topbar_enabled'] = !empty($in['topbar_enabled']) ? 1 : 0;
            $out['topbar_bg'] = (isset($in['topbar_bg']) && preg_match($hex_regex, $in['topbar_bg'])) ? $in['topbar_bg'] : $d['topbar_bg'];
            $out['topbar_fg'] = (isset($in['topbar_fg']) && preg_match($hex_regex, $in['topbar_fg'])) ? $in['topbar_fg'] : $d['topbar_fg'];
            $out['topbar_text'] = wp_kses_post($in['topbar_text'] ?? $d['topbar_text']);

            // Coupon Notice
            $out['coupon_notice_enabled'] = !empty($in['coupon_notice_enabled']) ? 1 : 0;
            $out['coupon_notice_text'] = sanitize_text_field($in['coupon_notice_text'] ?? $d['coupon_notice_text']);

            update_option(self::OPT_KEY, $out);

            wp_safe_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }
    }
    
    public function render_page() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            add_settings_error('gdos-ivaoff-notices', 'gdos-ivaoff-success', 'Ajustes guardados correctamente.', 'updated');
        }
        settings_errors('gdos-ivaoff-notices');

        $o = wp_parse_args(get_option(self::OPT_KEY, []), self::get_defaults());
        ?>
        <div class="wrap">
            <h1>IVA OFF</h1>
            <p>Descuento automático y banner de precios para Transferencia bancaria.</p>
            <div style="margin:10px 0 18px">
                <span style="display:inline-block;padding:.35em .7em;border-radius:4px;background:<?php echo IvaOff::is_active_now() ? '#16a34a':'#9ca3af'; ?>;color:#fff;font-weight:700;font-size:12px;">
                    ESTADO: <?php echo IvaOff::is_active_now() ? 'ACTIVO' : 'INACTIVO'; ?>
                </span>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>

                <h2>Ajustes Generales</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Activar IVA OFF</th>
                        <td><label><input type="checkbox" name="<?php echo self::OPT_KEY; ?>[enabled]" value="1" <?php checked(1, $o['enabled']); ?>> Habilitar sistema completo</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Programación</th>
                        <td>
                            <label>Inicio <input type="date" name="<?php echo self::OPT_KEY; ?>[start_date]" value="<?php echo esc_attr($o['start_date']); ?>"></label>
                            <label style="margin-left:12px;">Fin <input type="date" name="<?php echo self::OPT_KEY; ?>[end_date]" value="<?php echo esc_attr($o['end_date']); ?>"></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Porcentaje</th>
                        <td><input type="number" step="0.0001" min="0" max="1" name="<?php echo self::OPT_KEY; ?>[rate]" value="<?php echo esc_attr($o['rate']); ?>"> <span class="description">Ej: 0.1803</span></td>
                    </tr>
                </table>

                <h2>Mensajes en Checkout</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Mensaje Pago</th>
                        <td><textarea name="<?php echo self::OPT_KEY; ?>[message]" rows="3" class="large-text"><?php echo esc_textarea($o['message']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Badge</th>
                        <td><label><input type="checkbox" name="<?php echo self::OPT_KEY; ?>[badge_enabled]" value="1" <?php checked(1, $o['badge_enabled']); ?>> Mostrar badge</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Texto del Badge</th>
                        <td><input type="text" class="regular-text" name="<?php echo self::OPT_KEY; ?>[badge_text]" value="<?php echo esc_attr($o['badge_text']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Colores del Badge</th>
                        <td>
                            <label>Fondo <input type="text" class="gdos-color-picker" name="<?php echo self::OPT_KEY; ?>[badge_bg]" value="<?php echo esc_attr($o['badge_bg']); ?>"></label>
                            <label style="margin-left:12px;">Texto <input type="text" class="gdos-color-picker" name="<?php echo self::OPT_KEY; ?>[badge_fg]" value="<?php echo esc_attr($o['badge_fg']); ?>"></label>
                        </td>
                    </tr>
                </table>
                
                <h2>Aviso de Cupón</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Mostrar Aviso</th>
                        <td><label><input type="checkbox" name="<?php echo self::OPT_KEY; ?>[coupon_notice_enabled]" value="1" <?php checked(1, $o['coupon_notice_enabled']); ?>> Mostrar mensaje en cupones</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Texto del Aviso</th>
                        <td><input type="text" class="large-text" name="<?php echo self::OPT_KEY; ?>[coupon_notice_text]" value="<?php echo esc_attr($o['coupon_notice_text']); ?>"></td>
                    </tr>
                </table>

                <h2>Banner Superior en Producto</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Mostrar Banner</th>
                        <td><label><input type="checkbox" name="<?php echo self::OPT_KEY; ?>[topbar_enabled]" value="1" <?php checked(1, $o['topbar_enabled']); ?>> Mostrar banner</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Colores del Banner</th>
                        <td>
                            <label>Fondo <input type="text" class="gdos-color-picker" name="<?php echo self::OPT_KEY; ?>[topbar_bg]" value="<?php echo esc_attr($o['topbar_bg']); ?>"></label>
                            <label style="margin-left:12px;">Texto <input type="text" class="gdos-color-picker" name="<?php echo self::OPT_KEY; ?>[topbar_fg]" value="<?php echo esc_attr($o['topbar_fg']); ?>"></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Texto del Banner</th>
                        <td>
                            <textarea name="<?php echo self::OPT_KEY; ?>[topbar_text]" rows="3" class="large-text"><?php echo esc_textarea($o['topbar_text']); ?></textarea>
                            <p class="description" style="margin-top:.4em;">
                                Podés usar <code>{precio}</code>, <code>{precio_raw}</code>, <code>{rate}</code> y <code>{rate_percent}</code>.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <script>jQuery(function($){$('.gdos-color-picker').wpColorPicker();});</script>
        <?php
    }
}