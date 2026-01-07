<?php
// REFACTORIZADO: 2025-05-21 (Versión Final: IDs + Fix CLS)
// /modules/ofertas-flash/includes/Shortcode.php

namespace GDOS\Modules\OfertasFlash\includes;

use GDOS\Modules\OfertasFlash\OfertasFlash as Config;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class Shortcode
{
    private static bool $assets_enqueued = false;
    private $parent;

    public function __construct($parent = null)
    {
        $this->parent = $parent;
        \add_shortcode('gdos_ofertas_flash', [$this, 'render_base_shortcode']);
        \add_shortcode('gdos_flash_programado', [$this, 'render_wrapper_shortcode']);
    }

    /**
     * Wrapper: Decide qué productos mostrar basándose en la fecha actual.
     */
    public function render_wrapper_shortcode($atts)
    {
        $opts = Admin::get_options();
        $atts = \shortcode_atts([
            'title'  => '',
            'color'  => '',
            'cuotas' => '',
            'count'  => '',
            'day'    => ''
        ], $atts, 'gdos_flash_programado');

        if (! \shortcode_exists('gdos_ofertas_flash')) return '';

        // 1. Calcular el día de la campaña
        $day_index = 0;
        if (!empty($atts['day'])) {
            $day_index = \intval($atts['day']);
        } else {
            $start = \trim($opts['start_date']);
            if (!$start) return '';
            try {
                $now = new \DateTime('now', \wp_timezone());
                $sdt = new \DateTime($start . ' 00:00:00', \wp_timezone());

                // Si la campaña no ha empezado, no mostrar nada
                if ($now < $sdt) return '';

                $diff_days = (int) $sdt->diff($now->setTime(0, 0, 0))->days;
                $day_index = $diff_days + 1;
            } catch (\Exception $e) {
                return '';
            }
        }

        // 2. Obtener IDs guardados para ese día
        $days_data = $opts['days'] ?? [];
        if ($day_index < 1 || $day_index > \count($days_data)) return '';

        // Obtenemos el array de IDs (ahora son enteros gracias a la refactorización del Admin)
        $saved_ids = $days_data[$day_index - 1] ?? [];
        $saved_ids = \array_map('absint', $saved_ids);

        // 3. Validar existencia y stock de productos
        $valid_ids = [];
        foreach ($saved_ids as $pid) {
            if (!$pid) continue;
            $product = \wc_get_product($pid);
            if ($product && $product->is_in_stock() && $product->is_purchasable()) {
                $valid_ids[] = $pid;
            }
        }

        if (empty($valid_ids)) return '';

        // 4. Preparar atributos para pasar al shortcode base
        $campaign_title = $opts['campaign_title'] ?? 'Ofertas Flash';
        $media_id       = \absint($opts['title_media_id'] ?? 0);
        $media_type     = (string) ($opts['title_media_type'] ?? '');
        $color          = !empty($atts['color']) ? $atts['color'] : ($opts['title_color'] ?? '');
        $count          = !empty($atts['count']) ? \intval($atts['count']) : \count($valid_ids);

        // Borramos transient del modo random para asegurar frescura si se mezclan modos
        \delete_transient('gdos_ofertas_flash_' . \wp_date('Ymd'));

        $inner_atts = [
            'mode'       => 'ids',
            'ids'        => \implode(',', $valid_ids),
            'count'      => $count,
            'title'      => $atts['title'] ?: $campaign_title,
            'color'      => $color,
            'cuotas'     => $atts['cuotas'],
            'media_id'   => $media_id,
            'media_type' => $media_type,
            'campaign'   => $campaign_title,
        ];

        // Construcción del shortcode anidado
        $shortcode_str = '[gdos_ofertas_flash';
        foreach (\array_filter($inner_atts, fn($v) => $v !== '' && $v !== null) as $k => $v) {
            $shortcode_str .= \sprintf(' %s="%s"', $k, \esc_attr($v));
        }
        $shortcode_str .= ']';

        return \do_shortcode($shortcode_str);
    }

    /**
     * Base: Renderiza el HTML visual (Grid de productos).
     */
    public function render_base_shortcode($atts)
    {
        $this->enqueue_assets();

        // Iconos estáticos
        $icon_oca  = Assets::get('../assets/images/oca-icon.svg', __FILE__);
        $icon_buy  = Assets::get('../assets/images/buy-icon.svg', __FILE__);
        $icon_fire = Assets::get('../assets/images/fire-gif.gif', __FILE__);

        $atts = \shortcode_atts([
            'mode'       => 'random',
            'ids'        => '',
            'count'      => '6',
            'title'      => 'Cyber Fest',
            'color'      => '#2196F3',
            'cuotas'     => '12',
            'media_id'   => 0,
            'media_type' => '',
            'campaign'   => 'Ofertas Flash',
            'gif_id'     => 0 // legacy
        ], $atts, 'gdos_ofertas_flash');

        $opts = Admin::get_options();
        $count  = \max(1, \intval($atts['count']));
        $cuotas = \max(1, \intval($atts['cuotas']));

        // Obtención de IDs finales
        $t_key     = 'gdos_ofertas_flash_' . \wp_date('Ymd');
        $final_ids = [];

        if ($atts['mode'] === 'ids') {
            $final_ids = \array_map('intval', \explode(',', $atts['ids']));
        } else {
            // Modo Random (Fallback si no hay campaña programada)
            $final_ids = \get_transient($t_key);
            if (empty($final_ids)) {
                $products = \wc_get_products([
                    'status'       => 'publish',
                    'limit'        => $count * 3,
                    'orderby'      => 'rand',
                    'type'         => ['simple'],
                    'stock_status' => 'instock',
                    'return'       => 'ids'
                ]);
                $final_ids = \array_slice($products, 0, $count);
                \set_transient($t_key, $final_ids, 12 * \HOUR_IN_SECONDS);
            }
        }

        if (empty($final_ids)) return '<div class="gdos-flash-empty">No hay Ofertas Flash disponibles.</div>';

        // Renderizado de tarjetas
        $cards_html = '';
        foreach ($final_ids as $pid) {
            $product = \wc_get_product($pid);
            if (! $product || ! $product->is_purchasable() || ! $product->is_in_stock()) continue;

            $price_regular = (float) $product->get_price();
            if ($price_regular <= 0) continue;

            // Lógica de visualización de precios (Marketing: mostrar precio actual como oferta y tachar uno mayor)
            $price_display = $price_regular;
            // Simulamos un precio "original" más alto (+22%) si no hay oferta real configurada
            $price_strike  = $price_regular * 1.22;

            if ($product->is_on_sale()) {
                // Si hay oferta real en WC, respetamos los precios nativos
                $price_strike = $product->get_regular_price();
            }

            // Enlace directo al checkout para conversión rápida
            $link = \add_query_arg(['add-to-cart' => $pid, 'quantity' => 1], \wc_get_checkout_url());

            if ($product->is_type('variation')) {
                $link = \add_query_arg([
                    'add-to-cart'  => $product->get_parent_id(),
                    'variation_id' => $pid,
                    'quantity'     => 1
                ], \wc_get_checkout_url());
            }

            $campaign_label = \sprintf('Precio %s:', \esc_html($atts['campaign']));
            $cuota_valor    = $price_display / $cuotas;

            $cards_html .= \sprintf(
                '<div class="gdos-flash-card">
                    <a class="gdos-img" href="%s">%s</a>
                    <div class="gdos-body">
                        <div class="gdos-price-block">
                            <div class="gdos-price-main">
                                <span class="gcf-label">%s</span>
                                <span class="gcf-price gcf-price-off">%s</span>
                            </div>
                            <div class="gdos-price-regular">
                                <span class="gcf-label">Precio regular:</span>
                                <span class="gcf-price">%s</span>
                            </div>
                        </div>
                        <div class="gdos-cuotas">
                            <img class="gdos-cuotas-icon" src="%s" width="18" height="18" alt="">
                            <span>%d cuotas sin interés de %s</span>
                        </div>
                        <div class="gdos-title">%s</div>
                        <div class="gdos-spacer"></div>
                        <a class="gdos-buy" href="%s">
                            <img class="gdos-buy-icon" src="%s" alt="">
                            <span>Comprar ahora</span>
                        </a>
                    </div>
                </div>',
                \esc_url($product->get_permalink()),
                $product->get_image('woocommerce_thumbnail'),
                $campaign_label,
                \wc_price($price_display),
                \wc_price($price_strike),
                \esc_url($icon_oca['url']),
                $cuotas,
                \wc_price($cuota_valor),
                \esc_html($product->get_name()),
                \esc_url($link),
                \esc_url($icon_buy['url'])
            );
        }

        // Selección de icono (Media)
        $media_url = '';
        if (!empty($atts['media_id'])) {
            $media_url = \wp_get_attachment_url(\absint($atts['media_id']));
        } elseif (!empty($atts['gif_id'])) {
            $media_url = \wp_get_attachment_url(\absint($atts['gif_id']));
        }
        if (!$media_url) {
            $media_url = $icon_fire['url'];
        }

        // Timer: Medianoche
        $now  = new \DateTime('now', \wp_timezone());
        $midn = (clone $now)->setTime(0, 0, 0)->modify('+1 day');

        \ob_start();
?>
        <section class="gdos-flash-wrap"
            style="--gdos-accent: <?php echo \esc_attr($atts['color']); ?>;
                   --gdos-border-start: <?php echo \esc_attr($opts['border_start'] ?? ''); ?>;
                   --gdos-border-end: <?php echo \esc_attr($opts['border_end'] ?? ''); ?>;
                   --gdos-buy: <?php echo \esc_attr($opts['buy_color'] ?? ''); ?>;
                   --gdos-timer: <?php echo \esc_attr($opts['timer_color'] ?? ''); ?>;">

            <div class="gdos-flash-head">
                <h2 class="gdos-flash-title">
                    <?php if ($media_url): ?>
                        <img class="gdos-title-gif"
                            src="<?php echo \esc_url($media_url); ?>"
                            alt=""
                            width="30" height="30"
                            style="height:30px; width:auto; display:inline-block; vertical-align:middle;">
                    <?php endif; ?>
                    <span class="gdos-title-text"><?php echo \esc_html($atts['title']); ?></span>
                </h2>

                <div class="gdos-flash-global-timer" data-deadline="<?php echo \esc_attr($midn->getTimestamp()); ?>">
                    <span class="gdos-tt">⏰ Nuevos Productos:</span>
                    <span class="gdos-count">--:--:--</span>
                </div>
            </div>

            <div class="gdos-flash-grid">
                <div class="gdos-flash-track"><?php echo $cards_html; // PHPCS: ignore WordPress.Security.EscapeOutput 
                                                ?></div>
            </div>
        </section>
<?php
        return \ob_get_clean();
    }

    private function enqueue_assets(): void
    {
        if (self::$assets_enqueued) return;

        // Ruta relativa desde includes/ a assets/
        $css = Assets::get('../assets/css/frontend.css', __FILE__);
        $js  = Assets::get('../assets/js/frontend.js', __FILE__);

        \wp_enqueue_style('gdos-ofertas-flash-css', $css['url'], [], $css['ver']);
        \wp_enqueue_script('gdos-ofertas-flash-js', $js['url'], [], $js['ver'], true);

        self::$assets_enqueued = true;
    }
}
