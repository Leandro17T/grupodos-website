<?php
// /modules/WooDashboardWidget/module.php

if (! \defined('ABSPATH')) {
    exit;
}

// Carga la clase lógica
require_once __DIR__ . '/WooDashboardWidget.php';

// Retorna el Namespace completo de la clase para que tu Core la instancie
return 'GDOS\\Modules\\WooDashboardWidget\\WooDashboardWidget';
