<?php
// REFACTORIZADO: 2025-12-06
// /modules/etiquetas-envio-dinamicas/module.php

if (! \defined('ABSPATH')) {
    exit;
}

// Cargamos la clase principal
require_once __DIR__ . '/EtiquetasEnvioDinamicas.php';

// Devolvemos el nombre completo de la clase con su Namespace
return 'GDOS\\Modules\\EtiquetasEnvioDinamicas\\EtiquetasEnvioDinamicas';
