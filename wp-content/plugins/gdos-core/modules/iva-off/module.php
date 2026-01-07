<?php
// /modules/iva-off/module.php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/IvaOff.php';

/**
 * Helper GLOBAL para snippets externos
 */
if ( ! function_exists('gdos_is_ivaoff_active_now') ) {
    function gdos_is_ivaoff_active_now() {
        if (class_exists('GDOS\Modules\IvaOff\IvaOff')) {
            return \GDOS\Modules\IvaOff\IvaOff::is_active_now();
        }
        return false;
    }
}

return 'GDOS\\Modules\\IvaOff\\IvaOff';