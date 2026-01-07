<?php
// REFACTORIZADO: 2025-12-03

if (! \defined('ABSPATH')) {
    \exit;
}

/**
 * Carga de dependencias del módulo.
 */
require_once __DIR__ . '/ImprimirEtiquetas.php';

/**
 * Retornamos el Namespace completo de la clase principal
 * para que el Core de GDOS la instancie.
 */
return 'GDOS\\Modules\\ImprimirEtiquetas\\ImprimirEtiquetas';
