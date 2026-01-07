<?php
// REFACTORIZADO: 2025-12-06
// /modules/countdown-envio/module.php

if (! \defined('ABSPATH')) {
    exit;
}

// Cargamos la clase principal
require_once __DIR__ . '/CountdownEnvio.php';

// Devolvemos el nombre completo de la clase con su Namespace
return 'GDOS\\Modules\\CountdownEnvio\\CountdownEnvio';
