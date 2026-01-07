<?php
// REFACTORIZADO: 2025-05-21
// /modules/modo-comercial/includes/Admin.php

namespace GDOS\Modules\ModoComercial\includes;

use GDOS\Modules\ModoComercial\ModoComercial as Config;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class Admin
{
    /** Constantes */
    private const OPTION_GROUP  = 'gdos_modo_comercial_group';
    private const NONCE_ACTION  = 'gdos_mc_save_action';
    private const NONCE_FIELD   = 'gdos_mc_nonce';
    private const ADMINPOST_ACT = 'gdos_mc_save';

    /** Hook para comparar en enqueue */
    private string $page_hook = '';

    public function __construct()
    {
        \add_action('admin_menu', [$this, 'add_submenu_page']);
        \add_action('admin_init', [$this, 'register_settings'], 5);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Guardado manual seguro
        \add_action('admin_post_' . self::ADMINPOST_ACT, [$this, 'handle_save']);
    }

    public function add_submenu_page(): void
    {
        $this->page_hook = \add_submenu_page(
            Config::MENU_PARENT,
            \__('Modo Comercial', Config::TEXTDOMAIN),
            \__('Modo Comercial', Config::TEXTDOMAIN),
            'manage_options',
            Config::SLUG,
            [$this, 'render_page'],
            25
        );
    }

    public function register_settings(): void
    {
        \register_setting(
            self::OPTION_GROUP,
            Config::OPT_KEY,
            ['sanitize_callback' => [$this, 'sanitize_options']]
        );
    }

    public function enqueue_assets($hook): void
    {
        // Carga Condicional Estricta
        if (empty($this->page_hook) || $hook !== $this->page_hook) return;

        // Ruta relativa: estamos en /includes/, assets está en ../assets/
        $css = Assets::get('../assets/admin.css', __FILE__);
        $js  = Assets::get('../assets/admin.js', __FILE__);

        \wp_enqueue_style('gdos-mc-admin-css', $css['url'], [], $css['ver']);
        \wp_enqueue_script('gdos-mc-admin-js', $js['url'], ['jquery'], $js['ver'], true);
    }

    private function get_default_options(): array
    {
        $tz = \wp_timezone_string() ?: 'America/Montevideo';
        return [
            'enabled'         => 0,
            'page_id'         => 0,
            'start_datetime'  => '',
            'end_datetime'    => '',
            'tz'              => $tz,
            'primary_color'   => '#0ea5e9',
            'secondary_color' => '#111827',
            'accent_color'    => '#f59e0b',
            'custom_css'      => '',
        ];
    }

    public function sanitize_options($input): array
    {
        $out = $this->get_default_options();

        $out['enabled'] = isset($input['enabled']) ? 1 : 0;
        $out['page_id'] = isset($input['page_id']) ? \absint($input['page_id']) : 0;

        // Zona Horaria
        $tz_string = !empty($input['tz']) ? \sanitize_text_field($input['tz']) : (\wp_timezone_string() ?: 'UTC');
        try {
            $tz = new \DateTimeZone($tz_string);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
            $tz_string = 'UTC';
        }
        $out['tz'] = $tz_string;

        // Fechas
        $start_raw = isset($input['start_datetime']) ? \sanitize_text_field($input['start_datetime']) : '';
        $end_raw   = isset($input['end_datetime'])   ? \sanitize_text_field($input['end_datetime'])   : '';

        $start = $start_raw ? \DateTime::createFromFormat('Y-m-d\TH:i', $start_raw, $tz) : false;
        $end   = $end_raw   ? \DateTime::createFromFormat('Y-m-d\TH:i', $end_raw, $tz)   : false;

        // Validación de Rango
        if ($start && $end && $end <= $start) {
            \add_settings_error(Config::OPT_KEY, 'gdos-mc-range', \__('La fecha de fin debe ser posterior al inicio.', Config::TEXTDOMAIN));
            // No guardamos fechas inválidas para forzar al usuario a corregir
        } else {
            $out['start_datetime'] = $start ? $start->format('Y-m-d\TH:i') : '';
            $out['end_datetime']   = $end   ? $end->format('Y-m-d\TH:i')   : '';
        }

        // Colores (Validación Hex)
        $sanitize_hex = function ($color) {
            $color = \sanitize_hex_color($color);
            return $color ?: ''; // Retorna vacío si es inválido para usar default luego si se desea
        };

        $out['primary_color']   = $sanitize_hex($input['primary_color'] ?? '')   ?: '#0ea5e9';
        $out['secondary_color'] = $sanitize_hex($input['secondary_color'] ?? '') ?: '#111827';
        $out['accent_color']    = $sanitize_hex($input['accent_color'] ?? '')    ?: '#f59e0b';

        // CSS Custom (Permitir saltos de línea y CSS básico)
        $out['custom_css'] = \wp_strip_all_tags($input['custom_css'] ?? '');

        return $out;
    }

    /**
     * Guardado robusto vía admin-post.php
     */
    public function handle_save(): void
    {
        if (! \current_user_can('manage_options')) {
            \wp_die(\esc_html__('No tienes permisos suficientes.', 'default'));
        }

        $nonce = isset($_POST[self::NONCE_FIELD]) ? (string) $_POST[self::NONCE_FIELD] : '';
        if (! $nonce || ! \wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            \wp_die(\esc_html__('Error de seguridad (nonce inválido).', 'default'));
        }

        $input     = isset($_POST[Config::OPT_KEY]) && \is_array($_POST[Config::OPT_KEY]) ? $_POST[Config::OPT_KEY] : [];
        $sanitized = $this->sanitize_options($input);

        \update_option(Config::OPT_KEY, $sanitized);

        $redirect = \add_query_arg(
            ['page' => Config::SLUG, 'settings-updated' => 'true'],
            \admin_url('admin.php')
        );

        \wp_safe_redirect($redirect);
        exit;
    }

    public function render_page(): void
    {
        if (! \current_user_can('manage_options')) return;

        $opts = \get_option(Config::OPT_KEY, $this->get_default_options());
        // Merge defaults para asegurar que todas las keys existan
        $opts = \wp_parse_args($opts, $this->get_default_options());

        $tz   = $opts['tz'];
        $form_action = \admin_url('admin-post.php');

        // Calcular estado actual para mostrar feedback visual
        $status_message = 'Inactivo';
        if (class_exists(__NAMESPACE__ . '\Frontend')) {
            // Asumiendo que Frontend tiene un método estático para esto, si no, lo manejamos básico
            $status_data = Frontend::compute_active_status();
            $status_message = $status_data['message'] ?? 'Desconocido';
        }

?>
        <div class="wrap gdos-mc-wrap">
            <h1 class="gdos-mc-title">Modo Comercial</h1>

            <?php if (!empty($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Ajustes guardados.</p>
                </div>
            <?php endif; ?>

            <p class="gdos-mc-subtitle">Programa periodos (Cyber Fest, Cyber Monday, etc.) en los que la <strong>Home</strong> será reemplazada por una Landing Page específica.</p>

            <form method="post" action="<?php echo \esc_url($form_action); ?>" class="gdos-mc-form">
                <?php \wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
                <input type="hidden" name="action" value="<?php echo \esc_attr(self::ADMINPOST_ACT); ?>">

                <div class="gdos-mc-card">
                    <h2>Configuración Principal</h2>

                    <label class="gdos-mc-row">
                        <span>Activar Modo Comercial</span>
                        <div class="switch-wrapper">
                            <label class="switch">
                                <input type="checkbox" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[enabled]" <?php \checked(!empty($opts['enabled'])); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </label>

                    <label class="gdos-mc-row">
                        <span>Página a usar como Home</span>
                        <?php
                        \wp_dropdown_pages([
                            'name'              => Config::OPT_KEY . '[page_id]',
                            'echo'              => 1,
                            'show_option_none'  => __('— Selecciona una página —', Config::TEXTDOMAIN),
                            'option_none_value' => '0',
                            'selected'          => \absint($opts['page_id']),
                            'post_status'       => ['publish', 'private'], // Permitir privadas para testing
                            'class'             => 'regular-text',
                        ]);
                        ?>
                    </label>

                    <div class="gdos-mc-grid">
                        <label>
                            <span>Inicio (fecha y hora)</span>
                            <input type="datetime-local" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[start_datetime]" value="<?php echo \esc_attr($opts['start_datetime']); ?>">
                        </label>
                        <label>
                            <span>Fin (fecha y hora)</span>
                            <input type="datetime-local" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[end_datetime]" value="<?php echo \esc_attr($opts['end_datetime']); ?>">
                        </label>
                        <label>
                            <span>Zona horaria</span>
                            <input type="text" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[tz]" value="<?php echo \esc_attr($tz); ?>" placeholder="America/Montevideo">
                            <small>Ej: <code>America/Montevideo</code>.</small>
                        </label>
                    </div>
                </div>

                <div class="gdos-mc-card">
                    <h2>Estilos (Variables CSS)</h2>
                    <p class="description">Estos colores se inyectarán como variables CSS cuando el modo esté activo.</p>
                    <div class="gdos-mc-grid">
                        <label>
                            <span>Primario</span>
                            <input type="color" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[primary_color]" value="<?php echo \esc_attr($opts['primary_color']); ?>">
                        </label>
                        <label>
                            <span>Secundario</span>
                            <input type="color" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[secondary_color]" value="<?php echo \esc_attr($opts['secondary_color']); ?>">
                        </label>
                        <label>
                            <span>Acento</span>
                            <input type="color" name="<?php echo \esc_attr(Config::OPT_KEY); ?>[accent_color]" value="<?php echo \esc_attr($opts['accent_color']); ?>">
                        </label>
                    </div>

                    <label class="gdos-mc-row-col" style="margin-top: 15px;">
                        <span>CSS Adicional</span>
                        <textarea name="<?php echo \esc_attr(Config::OPT_KEY); ?>[custom_css]" rows="6" class="large-text code" placeholder="/* CSS que se cargará solo en la Landing Page */"><?php echo \esc_textarea($opts['custom_css']); ?></textarea>
                    </label>
                </div>

                <?php \submit_button(__('Guardar Cambios', Config::TEXTDOMAIN)); ?>

                <div class="gdos-mc-status-bar">
                    <?php echo \wp_kses_post($status_message); ?>
                </div>
            </form>
        </div>
<?php
    }
}
