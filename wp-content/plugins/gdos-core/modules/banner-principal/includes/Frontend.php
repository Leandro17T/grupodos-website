<?php
// REFACTORIZADO: 2025-01-12
// /modules/banner-principal/includes/Frontend.php

namespace GDOS\Modules\BannerPrincipal\includes;

use GDOS\Modules\BannerPrincipal\BannerPrincipal;
use DateTime;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit;
}

class Frontend
{
    private BannerPrincipal $module;
    private static bool $assets_enqueued = false;
    private const TIMEZONE = 'America/Montevideo';

    public function __construct(BannerPrincipal $module)
    {
        $this->module = $module;
        add_shortcode('banner_principal', [$this, 'render_shortcode']);
        add_shortcode('gdos_slider', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts): string
    {
        $atts = shortcode_atts([
            'autoplay' => '1',
            'interval' => '5000',
            'pause_on_hover' => '1',
        ], $atts, 'banner_principal');

        $post_id = (int) get_option(BannerPrincipal::OPT_SINGLE_ID, 0);
        if (!$post_id)
            return '';

        $this->enqueue_assets();

        $cache_key = BannerPrincipal::CACHE_KEY;
        $slides_data = get_transient($cache_key);

        if (false === $slides_data) {
            $slides_data = [
                'desktop' => $this->build_slide_objects(get_post_meta($post_id, '_gdos_slides_desktop', true)),
                'tablet' => $this->build_slide_objects(get_post_meta($post_id, '_gdos_slides_tablet', true)),
                'mobile' => $this->build_slide_objects(get_post_meta($post_id, '_gdos_slides_mobile', true)),
            ];
            set_transient($cache_key, $slides_data, HOUR_IN_SECONDS);
        }

        $now = new DateTime('now', new DateTimeZone(self::TIMEZONE));

        // CORRECCIÓN CRÍTICA: array_values para reindexar y forzar Array JSON
        $desktop = array_values($this->filter_active_slides($slides_data['desktop'], $now));
        $tablet = array_values($this->filter_active_slides($slides_data['tablet'], $now));
        $mobile = array_values($this->filter_active_slides($slides_data['mobile'], $now));

        if (empty($desktop) && empty($tablet) && empty($mobile)) {
            return '';
        }

        $uuid = wp_generate_uuid4();

        ob_start();
        ?>
        <div id="gdos-slider-<?php echo esc_attr($uuid); ?>" class="gdos-slider"
            data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>"
            data-interval="<?php echo esc_attr($atts['interval']); ?>"
            data-pause="<?php echo esc_attr($atts['pause_on_hover']); ?>"
            data-desktop='<?php echo esc_attr(wp_json_encode($desktop)); ?>'
            data-tablet='<?php echo esc_attr(wp_json_encode($tablet)); ?>'
            data-mobile='<?php echo esc_attr(wp_json_encode($mobile)); ?>' role="region" aria-roledescription="carousel">

            <div class="gdos-viewport">
                <div class="gdos-track">
                    <?php if (!empty($desktop[0])):
                        $first = $desktop[0];
                        ?>
                        <div class="gdos-slide is-active" style="width:100%;">
                            <?php if (!empty($first['link'])): ?><a href="<?php echo esc_url($first['link']); ?>"><?php endif; ?>
                                <img src="<?php echo esc_url($first['src']); ?>" alt="<?php echo esc_attr($first['alt']); ?>"
                                    width="<?php echo esc_attr($first['width']); ?>"
                                    height="<?php echo esc_attr($first['height']); ?>" fetchpriority="high" decoding="async"
                                    style="width:100%; height:auto; display:block;">
                                <?php if (!empty($first['link'])): ?></a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($desktop) > 1 || count($mobile) > 1): ?>
                    <button class="gdos-nav gdos-prev" aria-label="<?php esc_attr_e('Anterior', 'gdos-core'); ?>" type="button">
                        <svg viewBox="0 0 24 24" class="gdos-icon">
                            <path d="M15 6 L9 12 L15 18" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                    <button class="gdos-nav gdos-next" aria-label="<?php esc_attr_e('Siguiente', 'gdos-core'); ?>" type="button">
                        <svg viewBox="0 0 24 24" class="gdos-icon">
                            <path d="M9 6 L15 12 L9 18" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function build_slide_objects($items): array
    {
        if (!is_array($items))
            return [];

        $out = [];
        foreach ($items as $it) {
            $id = intval($it['id'] ?? 0);
            if (!$id)
                continue;

            $src_data = wp_get_attachment_image_src($id, 'full');
            if (!$src_data)
                continue;

            $out[] = [
                'src' => $src_data[0],
                'width' => $src_data[1],
                'height' => $src_data[2],
                'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
                'link' => $it['link'] ?? '',
                'priority' => intval($it['priority'] ?? 10),
                'date_from' => $it['date_from'] ?? '',
                'date_to' => $it['date_to'] ?? '',
                'time_from' => $it['time_from'] ?? '',
                'time_to' => $it['time_to'] ?? '',
                'days' => (string) ($it['days'] ?? ''),
                'countdown' => !empty($it['countdown']),
                'countdown_label' => $it['countdown_label'] ?? 'end',
            ];
        }

        usort($out, fn($a, $b) => ($a['priority'] <=> $b['priority']));
        return $out;
    }

    private function filter_active_slides(array $slides, DateTime $now): array
    {
        return array_filter($slides, function ($s) use ($now) {
            // 1. Filtro de Fechas
            if (!empty($s['date_from']) && $now->format('Y-m-d') < $s['date_from'])
                return false;
            if (!empty($s['date_to']) && $now->format('Y-m-d') > $s['date_to'])
                return false;

            // 2. Filtro de Días
            if (!empty($s['days'])) {
                $day_num = (int) $now->format('N');
                $allowed = array_map('intval', explode(',', $s['days']));
                if (!in_array($day_num, $allowed, true))
                    return false;
            }

            // 3. Lógica de Horas (CON SOPORTE PARA CRUCE DE MEDIANOCHE)
            if (!empty($s['time_from']) || !empty($s['time_to'])) {
                $now_time = $now->format('H:i');

                // Defaults si alguno está vacío
                $t_start = $s['time_from'] ?: '00:00';
                $t_end = $s['time_to'] ?: '23:59';

                if ($t_start <= $t_end) {
                    // CASO NORMAL (Ej: 09:00 a 17:00)
                    // La hora actual debe estar entre start y end
                    if ($now_time < $t_start || $now_time > $t_end) {
                        return false;
                    }
                } else {
                    // CASO NOCTURNO (Ej: 18:30 a 07:30)
                    // La hora actual NO puede estar en el "hueco" del día
                    // Es inválido si es menor que el inicio Y mayor que el fin
                    if ($now_time < $t_start && $now_time > $t_end) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    private function enqueue_assets(): void
    {
        if (self::$assets_enqueued)
            return;

        // Usamos los helpers del módulo principal para cargar assets de forma robusta
        $css = $this->module->asset('assets/public/css/public.css');
        if ($css['url']) {
            wp_enqueue_style('gdos-banner-public-css', $css['url'], [], $css['ver']);
        }

        $js = $this->module->asset('assets/public/js/public.js');
        if ($js['url']) {
            wp_enqueue_script('gdos-banner-public-js', $js['url'], [], $js['ver'], true);
        }

        self::$assets_enqueued = true;
    }
}

