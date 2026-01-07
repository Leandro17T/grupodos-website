<?php

namespace GDOS\Core;

// Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase Core
 *
 * Gestiona la inicialización del plugin, la carga de módulos y los hooks principales.
 */
final class Core {

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
    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Obtiene la instancia única de la clase.
     *
     * @return Core
     */
    public static function instance(): Core {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa el plugin.
     */
    public function init(): void {
        // Cargar text domain para traducciones.
        load_plugin_textdomain(
            'gdos-core',
            false,
            dirname( GDOS_CORE_BASENAME ) . '/languages/'
        );

        // Inicializar componentes del Core.
        if ( is_admin() ) {
            new Admin();
        }

        // Descubrir y arrancar los módulos.
        $this->discover_modules();
    }

    /**
     * Escanea el directorio de módulos, los valida y los inicializa.
     */
    private function discover_modules(): void {
        // Verificación de seguridad básica
        if (!defined('GDOS_CORE_PATH')) return;
        
        $modules_path = GDOS_CORE_PATH . 'modules/';

        if ( ! is_dir( $modules_path ) ) {
            return;
        }

        foreach ( scandir( $modules_path ) as $module_dir ) {
            if ( $module_dir === '.' || $module_dir === '..' ) {
                continue;
            }

            $module_path = $modules_path . $module_dir;
            $loader_file = $module_path . '/module.php';

            if ( is_dir( $module_path ) && file_exists( $loader_file ) ) {
                try {
                    $fqcn = require_once $loader_file;

                    if ( ! is_string( $fqcn ) || ! class_exists( $fqcn ) ) {
                        // Error silencioso para no romper el sitio si un modulo falla
                        continue; 
                    }

                    $module_instance = new $fqcn();

                    if ( ! method_exists( $module_instance, 'boot' ) ) {
                        continue;
                    }

                    // El módulo es válido, lo registramos.
                    $this->register_module( $module_dir, $module_instance );

                    // Arrancamos el módulo.
                    $module_instance->boot();

                } catch ( \Throwable $e ) {
                    // Manejo silencioso de errores para no romper el sitio.
                    error_log( '[GDOS Core] Error al cargar el módulo ' . $module_dir . ': ' . $e->getMessage() );
                }
            }
        }
    }

    /**
     * Registra un módulo en el sistema.
     *
     * @param string $slug Slug del módulo (nombre de la carpeta).
     * @param object $instance Instancia de la clase principal del módulo.
     */
    public function register_module( string $slug, object $instance ): void {
        $this->modules[ $slug ] = [
            'instance' => $instance,
            'path'     => GDOS_CORE_PATH . 'modules/' . $slug,
            'url'      => GDOS_CORE_URL . 'modules/' . $slug,
            'status'   => 'enabled', 
        ];
    }

    /**
     * Devuelve la lista de módulos detectados.
     *
     * @return array
     */
    public function get_modules(): array {
        return $this->modules;
    }
}