<?php
// REFACTORIZADO: 2025-12-06
// /modules/etiquetas-envio-dinamicas/EtiquetasEnvioDinamicas.php

namespace GDOS\Modules\EtiquetasEnvioDinamicas;

use GDOS\Core\ModuleInterface;
use DateTime;
use DateTimeZone;

if (! \defined('ABSPATH')) {
    exit;
}

class EtiquetasEnvioDinamicas implements ModuleInterface
{

    private string $option_name = 'gdos_feriados';
    private const PARENT_MENU_SLUG = 'grupodos-main';
    private const SUBMENU_SLUG     = 'gdos-feriados';
    private const NONCE_ACTION     = 'gdos_feriados_action';
    private const TIMEZONE         = 'America/Montevideo';

    public function boot(): void
    {
        \add_action('init', [$this, 'general_setup']);

        // Admin
        \add_action('admin_menu', [$this, 'add_feriados_submenu_page'], 30);

        // Frontend
        // Prioridad 5 para aparecer antes del título del producto
        \add_action('woocommerce_shop_loop_item_title', [$this, 'add_dynamic_label_above_product_title'], 5);
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
    }

    public function general_setup(): void
    {
        // Mantenemos esta limpieza de Google Fonts si es necesaria para tu theme
        if (\function_exists('enqueue_google_fonts')) {
            \remove_action('wp_enqueue_scripts', 'enqueue_google_fonts', 10);
        }
    }

    // --- LÓGICA DE ADMIN ---

    public function add_feriados_submenu_page(): void
    {
        \add_submenu_page(
            self::PARENT_MENU_SLUG,
            'Feriados Grupo Dos',
            'Feriados Grupo Dos',
            'manage_options',
            self::SUBMENU_SLUG,
            [$this, 'render_feriados_page'],
            30
        );
    }

    public function render_feriados_page(): void
    {
        if (! \current_user_can('manage_options')) return;

        // Procesar formulario antes de renderizar
        $message = $this->handle_form_submission();

        // Obtener datos
        $feriados = \get_option($this->option_name, []);
        if (! \is_array($feriados)) $feriados = [];

        $nonce_field = \wp_nonce_field(self::NONCE_ACTION, 'gdos_feriados_nonce', true, false);

        // Cargar vista
        require __DIR__ . '/views/admin/feriados-page.php';
    }

    private function handle_form_submission(): ?string
    {
        if (! isset($_POST['gdos_feriados_nonce'])) return null;
        if (! \wp_verify_nonce(\sanitize_key($_POST['gdos_feriados_nonce']), self::NONCE_ACTION)) return null;

        $feriados = \get_option($this->option_name, []);
        if (! \is_array($feriados)) $feriados = [];
        $changed = false;

        // Agregar fecha individual
        if (! empty($_POST['gdos_add_date'])) {
            $d = $this->normalize_date(\sanitize_text_field(\wp_unslash($_POST['gdos_add_date'])));
            if ($d) {
                $feriados[] = $d;
                $changed = true;
            }
        }

        // Agregar fechas masivas
        if (! empty($_POST['gdos_bulk_dates'])) {
            $raw = \sanitize_textarea_field(\wp_unslash($_POST['gdos_bulk_dates']));
            $parts = \preg_split('/[,\r\n]+/', $raw);
            foreach ($parts as $p) {
                $d = $this->normalize_date(\trim($p));
                if ($d) {
                    $feriados[] = $d;
                    $changed = true;
                }
            }
        }

        // Eliminar fechas
        if (! empty($_POST['gdos_delete']) && \is_array($_POST['gdos_delete'])) {
            $to_delete = \array_map('sanitize_text_field', \wp_unslash($_POST['gdos_delete']));
            $del = \array_filter(\array_map([$this, 'normalize_date'], $to_delete));
            $feriados = \array_diff($feriados, $del);
            $changed = true;
        }

        if ($changed) {
            $feriados = \array_values(\array_unique(\array_filter($feriados)));
            \sort($feriados);
            \update_option($this->option_name, $feriados);
            return '✅ Feriados actualizados correctamente.';
        }

        return null;
    }

    // --- LÓGICA DE FECHAS (CORE) ---

    private function normalize_date(?string $s): string
    {
        if (! $s) return '';
        $ts = \strtotime($s);
        return $ts ? \gmdate('Y-m-d', $ts) : '';
    }

    private function get_feriados(): array
    {
        $f = \get_option($this->option_name, []);
        return \is_array($f) ? \array_values(\array_unique(\array_filter(\array_map([$this, 'normalize_date'], $f)))) : [];
    }

    private function get_datetime_now(): DateTime
    {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }

    /**
     * Determina si una fecha (Y-m-d) es domingo o feriado.
     */
    private function is_holiday_or_sunday(string $ymd): bool
    {
        // Cacheamos los feriados en memoria estática para no leer options en cada iteración del loop
        static $cached_feriados = null;
        if ($cached_feriados === null) {
            $cached_feriados = $this->get_feriados();
        }

        $ts = \strtotime($ymd);
        $isSunday = (\gmdate('w', $ts) === '0'); // 0 = Domingo
        return $isSunday || \in_array($ymd, $cached_feriados, true);
    }

    private function next_business_day_from(string $ymd): string
    {
        $d = $ymd;
        // Límite de seguridad de 30 iteraciones para evitar bucles infinitos si hay configuración errónea
        $safety = 0;
        while ($this->is_holiday_or_sunday($d) && $safety < 30) {
            $d = \gmdate('Y-m-d', \strtotime($d . ' +1 day'));
            $safety++;
        }
        return $d;
    }

    private function spanish_weekday(int $timestamp): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        return $dias[(int) \gmdate('w', $timestamp)];
    }

    private function format_target_label(string $targetYmd, string $todayYmd): string
    {
        if ($targetYmd === $todayYmd) return 'hoy';

        $tsToday  = \strtotime($todayYmd);
        $tsTarget = \strtotime($targetYmd);

        // Diferencia en días redondeada
        $diffDays = (int) \round(($tsTarget - $tsToday) / \DAY_IN_SECONDS);

        if ($diffDays === 1) {
            return 'mañana';
        }

        $weekday = $this->spanish_weekday((int) $tsTarget);
        $dayNum  = \gmdate('j', $tsTarget);

        return $weekday . ' ' . $dayNum;
    }

    private function compute_target_date(string $mode): array
    {
        // IMPORTANTE: Usamos DateTime objects para no afectar el timezone global de PHP
        $now = $this->get_datetime_now();

        $todayYmd     = $now->format('Y-m-d');
        $current_day  = (int) $now->format('w'); // 0 (dom) - 6 (sab)
        $current_time = $now->format('H:i');

        $baseYmd     = $this->next_business_day_from($todayYmd);
        $isBaseToday = ($baseYmd === $todayYmd);

        $cutoff_weekday  = '16:00';
        $cutoff_saturday = '12:00';

        // Lógica de corte horario
        $is_within_cutoff =
            ($current_day >= 1 && $current_day <= 5 && $current_time < $cutoff_weekday) ||
            ($current_day === 6 && $current_time < $cutoff_saturday);

        if ($mode === 'flash' || $mode === 'express') {
            // Si hoy es día hábil y estamos antes del corte -> HOY
            if ($isBaseToday && $is_within_cutoff) {
                return ['today' => true,  'ymd' => $todayYmd];
            }

            // Si no llegamos al corte o hoy no es hábil -> Mañana (o siguiente hábil)
            // Si hoy era hábil pero pasamos la hora, base + 1 día. Si hoy no era hábil, base.
            $next_day = $isBaseToday ? \gmdate('Y-m-d', \strtotime($baseYmd . ' +1 day')) : $baseYmd;
            $target   = $this->next_business_day_from($next_day);

            return ['today' => false, 'ymd' => $target];
        }

        return ['today' => false, 'ymd' => $baseYmd];
    }

    private function display_dynamic_labels(string $tag): string
    {
        $now = $this->get_datetime_now();
        $todayYmd = $now->format('Y-m-d');

        // Helper visual
        $two_lines = function ($line1, $line2_html) {
            return '<span class="highlight">' . \esc_html($line1) . '</span><br><span class="subline">' . \wp_kses_post($line2_html) . '</span>';
        };

        if ($tag === 'express') {
            $res = $this->compute_target_date('express');
            if ($res['today']) return $two_lines('Envío Express', 'te llega hoy');

            $label = $this->format_target_label($res['ymd'], $todayYmd);
            return $two_lines('Envío Express', 'te llega ' . $label);
        }

        if ($tag === 'flash') {
            $res = $this->compute_target_date('flash');
            if ($res['today']) return $two_lines('Envío Flash', 'te llega hoy en 2 hs');

            $label = $this->format_target_label($res['ymd'], $todayYmd);
            return $two_lines('Envío Flash', 'te llega ' . $label);
        }

        return '';
    }

    // --- FRONTEND RENDERING ---

    public function add_dynamic_label_above_product_title(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            $product = \wc_get_product(\get_the_ID());
        }
        if (! $product) return;

        $pid = $product->get_id();

        // Check Tags (Optimizados con has_term, WP cachea esto)
        $is_express = \has_term(['express', 'Express'], 'product_tag', $pid);
        $is_flash   = \has_term(['flash-y-express', 'Flash y Express'], 'product_tag', $pid);

        if ($is_express) {
            $label_html = $this->display_dynamic_labels('express');
            echo '<span class="etiqueta-express">' . $label_html . '</span>';
        }

        if ($is_flash) {
            $label_html = $this->display_dynamic_labels('flash');
            echo '<span class="etiqueta-otro-tag">' . $label_html . '</span>';
        }
    }

    public function enqueue_frontend_styles(): void
    {
        $load_styles = false;

        // Condiciones de carga
        if (\function_exists('is_woocommerce') && \is_woocommerce()) $load_styles = true;
        if (\is_shop() || \is_product_category() || \is_product()) $load_styles = true;
        if (\is_front_page()) $load_styles = true;
        if (\is_page(67252)) $load_styles = true; // Página específica

        global $post;
        if (\is_a($post, 'WP_Post') && \has_shortcode($post->post_content, 'products')) $load_styles = true;

        if ($load_styles) {
            // Carga Robustecida: plugins_url + filemtime
            $css_rel = 'assets/css/frontend.css';
            $css_path = \plugin_dir_path(__FILE__) . $css_rel;

            if (\file_exists($css_path)) {
                \wp_enqueue_style(
                    'gdos-etiquetas-envio',
                    \plugins_url($css_rel, __FILE__),
                    [],
                    \filemtime($css_path)
                );
            }
        }
    }
}
