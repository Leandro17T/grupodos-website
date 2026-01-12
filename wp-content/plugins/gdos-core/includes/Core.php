<?php

namespace GDOS\Core;

// Evitar acceso directo.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Core
 *
 * Gestiona la inicialización del plugin, la carga de módulos y los hooks principales.
 */
final class Core
{

    /**
     * La única instancia de la clase.
     *
     * @var Core|null
     */
    private static ?Core $instance = null;

    /**
     * Módulos cargados y validados.
     *
     * @var array
     */
    public array $modules = [];

    /**
     * Constructor privado para el patrón Singleton.
     */
    private function __construct()
    {
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Obtiene la instancia única de la clase.
     *
     * @return Core
     */
    public static function instance(): Core
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa el plugin.
     */
    public function init(): void
    {
        // Cargar text domain para traducciones.
        load_plugin_textdomain(
            'gdos-core',
            false,
            dirname(GDOS_CORE_BASENAME) . '/languages/'
        );

        // Inicializar componentes del Core.
        if (is_admin()) {
            new Admin();
        }

        // Descubrir y arrancar los módulos.
        $this->discover_modules();
    }

    /**
     * Escanea el directorio de módulos, los valida y los inicializa.
     * Implementa caché mediante Transients para mejorar rendimiento.
     */
    private function discover_modules(): void
    {
        // Verificación de seguridad básica
        if (!defined('GDOS_CORE_PATH'))
            return;

        $modules_path = GDOS_CORE_PATH . 'modules/';

        if (!is_dir($modules_path)) {
            return;
        }

        // 1. Intentar obtener módulos de la caché (Transient)
        // Permitimos forzar la recarga con ?gdos_flush_modules=1 (solo admins) 
        // o si WP_DEBUG está activo para desarrollo.
        $force_flush = (isset($_GET['gdos_flush_modules']) && current_user_can('manage_options'));
        $is_debug = (defined('WP_DEBUG') && WP_DEBUG);

        $cached_modules = get_transient('gdos_core_active_modules');

        if (false === $cached_modules || $force_flush || $is_debug) {
            $cached_modules = $this->scan_modules($modules_path);

            // Guardamos en caché por 24 horas (o hasta que se limpie manualmente)
            set_transient('gdos_core_active_modules', $cached_modules, DAY_IN_SECONDS);
        }

        // 2. Instanciar y arrancar los módulos desde la lista (cacheada o fresca)
        foreach ($cached_modules as $slug => $data) {
            $fqcn = $data['class'];
            $path = $data['path']; // Usamos la ruta guardada

            // Doble chequeo por si el archivo fue borrado fisicamente pero sigue en caché
            if (!class_exists($fqcn)) {
                if (file_exists($path . '/module.php')) {
                    require_once $path . '/module.php';
                }
            }

            if (class_exists($fqcn)) {
                try {
                    $module_instance = new $fqcn();

                    if (!method_exists($module_instance, 'boot')) {
                        continue;
                    }

                    // El módulo es válido, lo registramos.
                    $this->register_module($slug, $module_instance);

                    // Arrancamos el módulo SOLO si está activo
                    if ($this->is_module_active($slug)) {
                        $module_instance->boot();
                    }

                } catch (\Throwable $e) {
                    error_log('[GDOS Core] Error al cargar módulo ' . $slug . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Escanea físicamente el directorio para encontrar módulos válidos.
     * 
     * @param string $modules_path Ruta al directorio de módulos.
     * @return array Lista de módulos válidos ['slug' => ['class' => '...', 'path' => '...']]
     */
    private function scan_modules(string $modules_path): array
    {
        $valid_modules = [];
        $dirs = scandir($modules_path);

        foreach ($dirs as $module_dir) {
            if ($module_dir === '.' || $module_dir === '..') {
                continue;
            }

            $module_path = $modules_path . $module_dir;
            $loader_file = $module_path . '/module.php';

            if (is_dir($module_path) && file_exists($loader_file)) {
                try {
                    // require_once devuelve el nombre de la clase (return 'Namespace\Class')
                    $fqcn = require_once $loader_file;

                    if (is_string($fqcn) && class_exists($fqcn)) {
                        $valid_modules[$module_dir] = [
                            'class' => $fqcn,
                            'path' => $module_path
                        ];
                    }
                } catch (\Throwable $e) {
                    error_log('[GDOS Core] Error scanning module ' . $module_dir . ': ' . $e->getMessage());
                }
            }
        }

        return $valid_modules;
    }

    /**
     * Registra un módulo en el sistema.
     *
     * @param string $slug Slug del módulo (nombre de la carpeta).
     * @param object $instance Instancia de la clase principal del módulo.
     */
    public function register_module(string $slug, object $instance): void
    {
        $this->modules[$slug] = [
            'instance' => $instance,
            'path' => GDOS_CORE_PATH . 'modules/' . $slug,
            'url' => GDOS_CORE_URL . 'modules/' . $slug,
            'status' => $this->is_module_active($slug) ? 'enabled' : 'disabled',
        ];
    }

    /**
     * Verifica si un módulo está activo.
     *
     * @param string $slug Slug del módulo.
     * @return bool
     */
    public function is_module_active(string $slug): bool
    {
        $options = get_option('gdos_core_modules_status', []);

        // Por defecto todos activos si no existe la opción para ese módulo
        if (!isset($options[$slug])) {
            return true;
        }

        return (bool) $options[$slug];
    }

    /**
     * Devuelve la lista de módulos detectados.
     *
     * @return array
     */
    public function get_modules(): array
    {
        return $this->modules;
    }
}