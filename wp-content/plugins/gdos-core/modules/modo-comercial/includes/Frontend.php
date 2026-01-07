<?php
// REFACTORIZADO: 2025-05-21
// /modules/modo-comercial/includes/Frontend.php

namespace GDOS\Modules\ModoComercial\includes;

use GDOS\Modules\ModoComercial\ModoComercial as Config;
use GDOS\Core\Assets;

if (! \defined('ABSPATH')) exit;

class Frontend
{
    /**
     * Cache estático para evitar recalcular fechas y zonas horarias
     * múltiples veces en la misma petición (WPO).
     */
    private static $status_cache = null;

    public function __construct()
    {
        // --- Lógica del Core del Módulo ---

        // Intercepta la Home solo si el modo está activo
        // Usamos prioridad 99 para asegurar que sobreescribimos defaults
        \add_filter('pre_option_show_on_front', [$this, 'maybe_force_show_on_front'], 99);
        \add_filter('pre_option_page_on_front', [$this, 'maybe_force_page_on_front'], 99);

        // Estilos globales y clases en el body
        \add_filter('body_class', [$this, 'body_class']);
        \add_action('wp_head', [$this, 'print_inline_css'], 100);

        // Avisos en el admin (UX)
        \add_action('admin_notices', [$this, 'maybe_admin_notice']);

        // --- Registro de Secciones (Shortcodes) ---
        \add_shortcode('gdos_mc_quick_access', [$this, 'render_quick_access']);
    }

    /**
     * Lógica para renderizar la sección "Quick Access" (Accesos Rápidos).
     */
    public function render_quick_access($atts)
    {
        // 1. Encolar CSS específico usando el Helper de Assets
        // Ruta: modules/modo-comercial/sections/css/quick-access.css
        // Desde includes/Frontend.php subimos un nivel y entramos a sections
        $css = Assets::get('../sections/css/quick-access.css', __FILE__);

        \wp_enqueue_style(
            'gdos-mc-section-qa',
            $css['url'],
            [],
            $css['ver']
        );

        // 2. Procesar atributos
        $atts = \shortcode_atts([], $atts, 'gdos_mc_quick_access');

        // 3. Renderizar vista
        \ob_start();

        $file = \dirname(__DIR__) . '/sections/quick-access.php';

        if (\file_exists($file)) {
            include $file;
        } else {
            // Fallback seguro: comentario oculto para debug
            echo '<!-- GDOS MC: Quick Access file not found -->';
        }

        return \ob_get_clean();
    }

    /**
     * Calcula si el Modo Comercial está activo basándose en fechas y configuración.
     * Implementa Runtime Cache para rendimiento.
     * * @return array { active: bool, message: string, page_id?: int }
     */
    public static function compute_active_status(): array
    {
        // Retornar caché si ya se calculó en esta petición
        if (self::$status_cache !== null) {
            return self::$status_cache;
        }

        $defaults = [
            'enabled'        => 0,
            'page_id'        => 0,
            'start_datetime' => '',
            'end_datetime'   => '',
            'tz'             => (\wp_timezone_string() ?: 'America/Montevideo'),
        ];

        // Obtenemos opción cruda y mergeamos defaults
        $opts = \get_option(Config::OPT_KEY, []);
        $opts = \wp_parse_args($opts, $defaults);

        // 1. Check Enabled
        if (empty($opts['enabled'])) {
            return self::$status_cache = ['active' => false, 'message' => 'Modo Comercial desactivado.'];
        }

        // 2. Check Página Válida
        $page_id = (int) $opts['page_id'];
        if (!$page_id || ! \get_post_status($page_id)) {
            return self::$status_cache = ['active' => false, 'message' => 'Error: Página de destino no válida o no existe.'];
        }

        // 3. Check Fechas configuradas
        if (empty($opts['start_datetime']) || empty($opts['end_datetime'])) {
            return self::$status_cache = ['active' => false, 'message' => 'Error: Fechas de inicio/fin incompletas.'];
        }

        // 4. Cálculo de Tiempo
        try {
            // Zona horaria segura
            try {
                $tz = new \DateTimeZone($opts['tz']);
            } catch (\Exception $e) {
                $tz = new \DateTimeZone('UTC');
            }

            $now   = new \DateTime('now', $tz);
            $start = \DateTime::createFromFormat('Y-m-d\TH:i', $opts['start_datetime'], $tz);
            $end   = \DateTime::createFromFormat('Y-m-d\TH:i', $opts['end_datetime'], $tz);

            if (! $start || ! $end) {
                return self::$status_cache = ['active' => false, 'message' => 'Error: Formato de fechas inválido.'];
            }

            // --- ESTADO ACTIVO ---
            if ($now >= $start && $now <= $end) {
                $fmt = 'd/m/Y H:i';
                return self::$status_cache = [
                    'active'  => true,
                    'message' => \sprintf(
                        'ACTIVO hasta el %s (%s). Redirigiendo a ID %d.',
                        $end->format($fmt),
                        $tz->getName(),
                        $page_id
                    ),
                    'page_id' => $page_id,
                ];
            }

            $fmt = 'd/m H:i';
            // --- ESTADO PROGRAMADO ---
            if ($now < $start) {
                return self::$status_cache = [
                    'active'  => false,
                    'message' => \sprintf(
                        'Programado: %s al %s.',
                        $start->format($fmt),
                        $end->format($fmt)
                    )
                ];
            }

            // --- ESTADO FINALIZADO ---
            return self::$status_cache = ['active' => false, 'message' => 'Finalizado (fuera de fecha).'];
        } catch (\Exception $e) {
            return self::$status_cache = ['active' => false, 'message' => 'Error crítico de cálculo de fecha.'];
        }
    }

    // --- Hooks de Intercepción de Home ---

    public function maybe_force_show_on_front($value)
    {
        // No interceptar en admin o customizer
        if (\is_admin() || \is_customize_preview()) return $value;

        $status = self::compute_active_status();
        if ($status['active'] ?? false) {
            return 'page'; // Fuerza a WordPress a usar una "Página estática"
        }
        return $value;
    }

    public function maybe_force_page_on_front($value)
    {
        if (\is_admin() || \is_customize_preview()) return $value;

        $status = self::compute_active_status();
        if (($status['active'] ?? false) && !empty($status['page_id'])) {
            // Verifica última vez que la página esté publicada para evitar 404
            if (\get_post_status($status['page_id']) === 'publish') {
                return $status['page_id']; // Devuelve el ID de nuestra Landing Page
            }
        }
        return $value;
    }

    // --- Estilos y Clases ---

    public function body_class($classes)
    {
        $status = self::compute_active_status();
        if ($status['active'] ?? false) {
            $classes[] = 'modo-comercial-activo';
        }
        return $classes;
    }

    public function print_inline_css(): void
    {
        $status = self::compute_active_status();

        // Solo inyectar si está activo O si estamos previsualizando (opcional, aquí solo activo)
        if (! ($status['active'] ?? false)) return;

        $opts = \get_option(Config::OPT_KEY, []);

        // Sanitización de salida (Hex o Fallback)
        $sanitize_hex = function ($c, $d) {
            return \sanitize_hex_color($c) ?: $d;
        };

        $primary   = $sanitize_hex($opts['primary_color']   ?? '', '#0ea5e9');
        $secondary = $sanitize_hex($opts['secondary_color'] ?? '', '#111827');
        $accent    = $sanitize_hex($opts['accent_color']    ?? '', '#f59e0b');

        // CSS Custom (strip_tags básico para evitar cierre de style)
        $custom    = \wp_strip_all_tags($opts['custom_css'] ?? '');

        echo "<style id='gdos-modo-comercial-inline'>
            :root {
                --gdos-mc-primary: {$primary};
                --gdos-mc-secondary: {$secondary};
                --gdos-mc-accent: {$accent};
            }
            body.modo-comercial-activo .gdos-mc-primary { color: var(--gdos-mc-primary); }
            body.modo-comercial-activo .gdos-mc-bg-primary { background-color: var(--gdos-mc-primary); }
            /* Custom CSS */
            {$custom}
        </style>";
    }

    // --- Admin Notices ---

    public function maybe_admin_notice(): void
    {
        if (! \current_user_can('manage_options')) return;

        // Solo mostrar en la página del módulo para no spammear todo el admin
        if (! isset($_GET['page']) || $_GET['page'] !== Config::SLUG) return;

        $status = self::compute_active_status();

        // Alerta: Activo pero sin página válida
        if (($status['active'] ?? false) && empty($status['page_id'])) {
            echo '<div class="notice notice-error"><p><strong>⚠️ Atención:</strong> El Modo Comercial está activo por fecha, pero no has configurado una página válida o no está publicada. La Home no cambiará.</p></div>';
        }
    }
}
