<?php

namespace GDOS\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BaseModule
 *
 * Clase abstracta que provee funcionalidad común a todos los módulos.
 */
abstract class BaseModule implements ModuleInterface
{

    /**
     * Renderiza una vista del módulo.
     *
     * @param string $view_path Ruta relativa a la vista dentro del módulo (sin .php).
     * @param array  $data      Datos para pasar a la vista.
     * @param bool   $echo      Si true, imprime la vista. Si false, la retorna.
     * @return string|void
     */
    public function view(string $view_path, array $data = [], bool $echo = false)
    {
        // Obtener la ruta del archivo hijo que está heredando (el módulo real)
        $reflector = new \ReflectionClass($this);
        $module_dir = dirname($reflector->getFileName());

        $file = $module_dir . '/' . $view_path . '.php';

        if (!file_exists($file)) {
            return '';
        }

        // Extraer datos para que estén disponibles como variables
        if (!empty($data)) {
            extract($data);
        }

        ob_start();
        include $file;
        $content = ob_get_clean();

        if ($echo) {
            echo $content;
        } else {
            return $content;
        }
    }

    /**
     * Obtiene la URL de un asset dentro del módulo.
     *
     * @param string $path Ruta relativa al asset (ej: 'assets/css/style.css').
     * @return string URL completa del asset.
     */
    public function asset_url(string $path): string
    {
        $reflector = new \ReflectionClass($this);
        $module_file = $reflector->getFileName();

        $asset = Assets::get($path, $module_file);
        return $asset['url'];
    }

    /**
     * Obtiene datos de un asset (url, ver) para encolar.
     * 
     * @param string $path Ruta relativa al asset.
     * @return array ['url' => ..., 'ver' => ...]
     */
    public function asset(string $path): array
    {
        $reflector = new \ReflectionClass($this);
        $module_file = $reflector->getFileName();

        return Assets::get($path, $module_file);
    }
}
