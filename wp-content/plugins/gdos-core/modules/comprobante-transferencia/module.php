<?php
// REFACTORIZADO: 2025-12-06
// /modules/comprobante-transferencia/module.php

if (! \defined('ABSPATH')) {
    exit;
}

// Cargamos la clase principal
require_once __DIR__ . '/ComprobanteTransferencia.php';

// Devolvemos el nombre completo de la clase con su Namespace
return 'GDOS\\Modules\\ComprobanteTransferencia\\ComprobanteTransferencia';
