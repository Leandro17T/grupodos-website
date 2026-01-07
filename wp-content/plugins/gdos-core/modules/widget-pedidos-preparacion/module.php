<?php
// REFACTORIZADO: 2025-05-21
// /modules/widget-pedidos-preparacion/module.php

if (! \defined('ABSPATH')) exit;

// Carga manual de la clase (Seguridad por si no hay Autoloader PSR-4 mapeado a esta carpeta)
require_once __DIR__ . '/WidgetPedidosPreparacion.php';

// Devolvemos el Nombre Completamente Calificado (FQCN) de la clase para que el Core la instancie
return 'GDOS\\Modules\\WidgetPedidosPreparacion\\WidgetPedidosPreparacion';
