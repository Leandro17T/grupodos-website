<?php
// REFACTORIZADO: 2025-12-06
// /modules/garantia-producto/module.php

if (! \defined('ABSPATH')) {
    exit;
}

// Cargamos la clase principal
require_once __DIR__ . '/GarantiaProducto.php';

// Retornamos el namespace completo
return 'GDOS\\Modules\\GarantiaProducto\\GarantiaProducto';
