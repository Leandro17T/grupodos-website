# GDOS Core Plugin

Este es un plugin base (core) diseñado para servir como un framework para cargar y gestionar módulos o snippets de forma centralizada y ordenada en WordPress.

**Versión:** 1.0.0  
**Compatible con WordPress:** 6.0 o superior  
**Compatible con PHP:** 7.4 o superior  
**Licencia:** GPLv2 o posterior

---

## Instalación

1.  Descarga el archivo `.zip` del plugin.
2.  Ve a tu panel de WordPress -> Plugins -> Añadir nuevo.
3.  Haz clic en "Subir plugin" y selecciona el archivo `.zip`.
4.  Activa el plugin.

Una vez activado, aparecerá un nuevo menú "GDOS Core" en el panel de administración de WordPress.

---

## Cómo Crear un Nuevo Módulo

La arquitectura está diseñada para que añadir nuevas funcionalidades sea rápido y estandarizado. Sigue estos pasos para crear tu propio módulo:

### 1. Crear la Carpeta del Módulo

Dentro del directorio `/modules/`, crea una nueva carpeta para tu módulo. El nombre de la carpeta será el "slug" del módulo (usa minúsculas y guiones).

**Ejemplo:** `/modules/mi-primer-modulo/`

### 2. Crear el Archivo de Carga `module.php`

Dentro de la carpeta de tu módulo, crea un archivo llamado `module.php`. Este archivo tiene una única responsabilidad: **retornar el Nombre de Clase Completamente Cualificado (FQCN)** de la clase principal de tu módulo.

**Ejemplo (`/modules/mi-primer-modulo/module.php`):**

```php
<?php
// Requerir aquí la clase principal del módulo.
require_once __DIR__ . '/MiPrimerModulo.php';

// Retornar el FQCN. ¡Esto es crucial!
return 'GDOS\\Modules\\MiPrimerModulo\\MiPrimerModulo';