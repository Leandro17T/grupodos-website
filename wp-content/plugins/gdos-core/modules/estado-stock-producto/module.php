<?php
// REFACTORIZADO: 2025-12-06
// /modules/estado-stock-producto/module.php

if (! \defined('ABSPATH')) {
    exit;
}

// Cargamos la clase principal
require_once __DIR__ . '/EstadoStockProducto.php';

// Devolvemos el nombre completo de la clase con su Namespace
return 'GDOS\\Modules\\EstadoStockProducto\\EstadoStockProducto';
