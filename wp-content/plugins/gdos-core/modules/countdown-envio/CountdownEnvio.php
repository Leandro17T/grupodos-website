<?php
// REFACTORIZADO: 2025-12-06 (Actualización de Textos y Lógica)
// /modules/countdown-envio/CountdownEnvio.php

namespace GDOS\Modules\CountdownEnvio;

use GDOS\Core\ModuleInterface;
use DateTime;
use DateTimeZone;

if (! \defined('ABSPATH')) {
    exit;
}

class CountdownEnvio implements ModuleInterface
{

    private const TIMEZONE = 'America/Montevideo';

    public function boot(): void
    {
        \add_shortcode('cuenta_regresiva_envio', [$this, 'render_shortcode']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function render_shortcode(): string
    {
        if (! \function_exists('is_product') || ! \is_product()) {
            return '';
        }

        $base_url = \plugins_url('', __FILE__);
        $icon_url = $base_url . '/assets/images/truck-icon.svg';

        \ob_start();
?>
        <div class="gdos-countdown-clean-wrapper" style="display:none;">
            <div class="gdos-countdown-main-row">
                <img src="<?php echo \esc_url($icon_url); ?>" class="gdos-countdown-icon" alt="Envío" width="24" height="24" />

                <div class="gdos-countdown-data">
                    <div class="gdos-countdown-content">
                    </div>
                </div>
            </div>

            <div class="gdos-countdown-footer">
                <small><?php \esc_html_e('Envío seguro y garantizado', 'gdos-core'); ?></small>
            </div>
        </div>
<?php
        return \ob_get_clean();
    }

    public function enqueue_assets(): void
    {
        if (! \function_exists('is_product') || ! \is_product()) {
            return;
        }

        $base_path = \plugin_dir_path(__FILE__);
        $base_url  = \plugin_dir_url(__FILE__);

        // CSS
        $css_file = 'assets/css/frontend.css';
        if (\file_exists($base_path . $css_file)) {
            \wp_enqueue_style('gdos-countdown-envio-css', $base_url . $css_file, [], \filemtime($base_path . $css_file));
        }

        // JS
        $js_file = 'assets/js/frontend.js';
        if (\file_exists($base_path . $js_file)) {
            \wp_enqueue_script('gdos-countdown-envio-js', $base_url . $js_file, [], \filemtime($base_path . $js_file), true);

            // Configuración de Tiempo y Textos
            $now = new DateTime('now', new DateTimeZone(self::TIMEZONE));
            $feriados = \get_option('gdos_feriados', []);
            $feriados_norm = [];

            if (\is_array($feriados)) {
                foreach ($feriados as $d) {
                    $ts = \strtotime($d);
                    if ($ts) $feriados_norm[] = \date('Y-m-d', $ts);
                }
            }

            \wp_localize_script('gdos-countdown-envio-js', 'gdosCountdownData', [
                'holidays'        => \array_values(\array_unique($feriados_norm)),
                'server_time_iso' => $now->format('c'),
                'cutoff_weekday'  => '16:00',
                'cutoff_saturday' => '12:00',
                // Textos Específicos Solicitados
                'texts'           => [
                    'timer_prefix' => \__('Te llega hoy comprando dentro de', 'gdos-core'),
                    'tomorrow'     => \__('Recibe el pedido mañana en tu domicilio', 'gdos-core'),
                    'future_date'  => \__('Te llega a tu domicilio el día %s', 'gdos-core'), // %s = "lunes 8 dic"
                ]
            ]);
        }
    }
}
