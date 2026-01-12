<?php
/**
 * Script CLI para generar un nuevo módulo en GDOS Core.
 * Uso: php bin/create-module.php "Nombre del Módulo"
 */

if (php_sapi_name() !== 'cli') {
    exit("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

if (count($argv) < 2) {
    exit("Uso: php bin/create-module.php \"Nombre del Módulo\"\n");
}

$module_name = $argv[1];
$slug = strtolower(str_replace(' ', '-', $module_name));
// Eliminar acentos y caracteres raros del slug
$slug = preg_replace('/[^a-z0-9-]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $slug));

$namespace_suffix = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
$class_name = $namespace_suffix;

// Rutas
$base_dir = dirname(__DIR__);
$module_dir = $base_dir . '/modules/' . $slug;
$assets_dir = $module_dir . '/assets';
$css_dir = $assets_dir . '/css';
$js_dir = $assets_dir . '/js';
$images_dir = $assets_dir . '/images';

echo "Creando módulo '$module_name' ($slug)...\n";

// 1. Crear directorios
if (is_dir($module_dir)) {
    exit("Error: El módulo '$slug' ya existe.\n");
}

mkdir($module_dir, 0755, true);
mkdir($css_dir, 0755, true);
mkdir($js_dir, 0755, true);
mkdir($images_dir, 0755, true);

// 2. Crear module.php
$module_php_content = <<<PHP
<?php
// /modules/{$slug}/module.php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/{$class_name}.php';

return 'GDOS\\Modules\\{$namespace_suffix}\\{$class_name}';
PHP;

file_put_contents($module_dir . '/module.php', $module_php_content);

// 3. Crear Class.php
$class_php_content = <<<PHP
<?php
// /modules/{$slug}/{$class_name}.php

namespace GDOS\Modules\\{$namespace_suffix};
use GDOS\Core\BaseModule;

if ( ! defined( 'ABSPATH' ) ) exit;

class {$class_name} extends BaseModule {

    public function boot(): void {
        // add_action('wp_enqueue_scripts', [\$this, 'scripts']);
        // add_shortcode('{$slug}', [\$this, 'render']);
    }

    public function scripts(): void {
        \$css = \$this->asset('assets/css/style.css');
        
        // wp_enqueue_style(
        //     'gdos-{$slug}',
        //     \$css['url'],
        //     [],
        //     \$css['ver']
        // );
    }

    public function render(): string {
        return "Hola desde {$module_name}";
    }
}
PHP;

file_put_contents($module_dir . '/' . $class_name . '.php', $class_php_content);

// 4. Crear assets vacíos
file_put_contents($css_dir . '/style.css', "/* Styles for {$module_name} */\n");
file_put_contents($js_dir . '/script.js', "// Scripts for {$module_name}\n");

echo "¡Módulo creado exitosamente!\n";
echo "Ubicación: modules/{$slug}\n";
