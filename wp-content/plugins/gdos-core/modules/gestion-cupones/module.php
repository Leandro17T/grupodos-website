<?php
// REFACTORIZADO: 2025-12-06
// /modules/gestion-cupones/module.php

if (! \defined('ABSPATH')) {
	exit;
}

// Cargamos la clase principal del módulo
require_once __DIR__ . '/GestionCupones.php';

// Devolvemos el nombre completo de la clase con su Namespace para el registro en el Core
return 'GDOS\\Modules\\GestionCupones\\GestionCupones';
