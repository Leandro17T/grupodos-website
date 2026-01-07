*** Seguimiento de envíos en Woo ***

Versión 3.1.0, 28 noviembre 2023
------------------------------------------------------------------------------------
* Fix     - Se previene un error en caso de estar activado HPOS
* Fix     - Añadido el text domain a dos textos que habían quedado fuera de la traducción
* Update  - Actualizado el naming de Woo
* Update  - Soporte para WordPress 6.4
* Update  - Soporte para Woo 8.3
* Update  - Actualización de ACF Pro a la versión 6.2.3
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.0.1, 3 junio 2023
------------------------------------------------------------------------------------
* Mejora  - Se realizan comprobaciones adicionales antes de guardar los datos de seguimiento del pedido
* Fix     - Los estilos de la metabox no se cargaban correctamente en la creación de pedidos desde el escritorio

Versión 3.0.0, 30 mayo 2023
------------------------------------------------------------------------------------
* Añadido - Soporte para la nueva característica HPOS de WooCommerce 8 (beta)
* Mejora  - Se previenen avisos por creación de propiedades dinámicas en PHP8.2+
* Update  - La versión mínima requerida de PHP pasa a ser 7.3
* Update  - Soporte para WordPress 6.3
* Update  - Soporte para WooCommerce 7.8
* Update  - Actualización de ACF Pro a la versión 6.1.6

Versión 2.3.0, 4 julio 2022
------------------------------------------------------------------------------------
* Update  - Soporte para WordPress 6.1
* Update  - Soporte para WooCommerce 6.7
* Update  - Actualización de ACF Pro a la versión 5.12.2
* Update  - Actualizada la librería PUC a la versión 4.11

Versión 2.2.5, 3 agosto 2021
------------------------------------------------------------------------------------
* Añadido - Cabecera de versión mínima de PHP requerida
* Añadido - Añadida URL de seguimiento para Zeleris en las agencias creadas por defecto
* Update  - Soporte para WordPress 5.9
* Update  - Soporte para WooCommerce 5.6
* Update  - Actualización de ACF Pro a la versión 5.9.9

Versión 2.2.4, 26 junio 2021
------------------------------------------------------------------------------------
* Fix     - El texto de seguimiento podía mostrar la fecha actual como fecha de envío en lugar de la establecida en la metabox
* Update  - Soporte para WooCommerce 5.5

Versión 2.2.3, 25 junio 2021
------------------------------------------------------------------------------------
* Añadido - El texto de información de seguimiento incluye la fecha de envío si ésta se ha establecido
* Update  - Soporte para WooCommerce 5.4
* Update  - Actualización de ACF Pro a la versión 5.9.7
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.2.2, 24 marzo 2021
------------------------------------------------------------------------------------
* Mejora  - No se muestra botón/enlace si no se ha establecido la URL de seguimiento para la agencia
* Mejora  - Mejoras de eficiencia en el init
* Fix     - Espaciado del párrafo en el texto añadido a la notificación de Pedido completado
* Update  - Soporte para WordPress 5.8
* Update  - Soporte para WooCommerce 5.2
* Update  - Actualización de ACF Pro a la versión 5.9.5

Versión 2.2.1, 21 enero 2021
------------------------------------------------------------------------------------
* Mejora - Se elimina la columna Fecha de la pantalla de administración de agencias

Versión 2.2.0, 21 enero 2021
------------------------------------------------------------------------------------
* Añadido - Botón para el seguimiento en la lista de pedidos del cliente en el área Mi cuenta
* Añadido - Mensajes personalizados en la creación/edición de agencias
* Añadido - Filtro ejr_acf_lite para deshabilitar la constante ACF_LITE definida por el plugin
* Añadido - Se incluye el archivo loco.xml para configuración automática de Loco Translate
* Mejora  - La URL de seguimiento en la página de listado de agencias sólo es clicable si no incluye variables
* Mejora  - Se cambia la cabecera de la columna de agencias de Título a Agencia
* Mejora  - Mejora de la eficiencia mediante el uso de la caché de objetos para la query de recuperación de agencias
* Mejora  - Mejoras de eficiencia en el init
* Update  - Soporte para WordPress 5.7
* Update  - Soporte para WooCommerce 5.0
* Update  - Actualización de ACF Pro a la versión 5.9.4
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.1.4, 3 julio 2020
------------------------------------------------------------------------------------
* Mejora - Mejoras de eficiencia en el init
* Update - Actualizada la URL de seguimiento para Correos
* Update - Actualización de ACF Pro a la versión 5.8.12
* Update - Plugin probado con WordPress 5.4.3-alpha
* Update - Plugin probado con WooCommerce 4.3.0-RC2

Versión 2.1.3, 27 febrero 2020
------------------------------------------------------------------------------------
* Mejora - Mejoras de eficiencia en el área de administración

Versión 2.1.2, 27 febrero 2020
------------------------------------------------------------------------------------
* Update - Actualización de ACF Pro a la versión 5.8.11
* Update - Actualizada la librería PUC a la versión 4.9
* Update - Plugin probado con WordPress 5.4.2-alpha
* Update - Plugin probado con WooCommerce 4.2.0-beta

Versión 2.1.1, 27 febrero 2020
------------------------------------------------------------------------------------
* Update - Cambiado el submenú desde el menú Productos al menú WooCommerce
* Update - Actualizada la URL por defecto de seguimiento para Envialia
* Update - Actualizada la URL por defecto de seguimiento para Correos Express
* Update - Actualizada la URL por defecto de seguimiento para Nacex
* Update - Actualizada la URL por defecto de seguimiento para Redyser
* Update - Actualización de ACF Pro a la versión 5.8.7
* Update - Plugin probado con WordPress 5.3.3-alpha
* Update - Plugin probado con WooCommerce 4.0.0-beta

Versión 2.1.0, 15 octubre 2019
------------------------------------------------------------------------------------
* Mejora  - Las agencias se pueden reordenar mediante operaciones de arrastrar y soltar
* Mejora  - Se utiliza la función más eficiente determine_locale para determinar el idioma si se está usando WordPress 5.0+
* Añadido - El listado de agencias muestra la URL de seguimiento establecida para cada una de ellas
* Update  - Actualización de la plantilla de traducción
* Update  - Actualización de ACF Pro a la versión 5.8.5
* Update  - Plugin probado con WordPress 5.2.5-alpha
* Update  - Plugin probado con WooCommerce 3.8.0-beta

Versión 2.0.0, 8 marzo 2019
------------------------------------------------------------------------------------
* Añadido - La información de seguimiento se muestra ahora en la vista rápida del pedido
* Añadido - Función de ayuda para recuperar todos los datos de seguimiento con el ID del pedido
* Añadido - Se incluye una columna con el número de seguimiento en la pantalla de listado de pedidos con un enlace al seguimiento
* Añadido - Si está disponible la información de seguimiento se añade también al correo electrónico de detalles del pedido
* Añadido - Compatibilidad con el plugin WooCommerce Email Customizer with Drag and Drop Email Builder
* Mejora  - Implementación de un patrón singleton en la llamada a la clase principal del plugin
* Mejora  - Se declara el alcance de los métodos
* Mejora  - Se añade un mensaje de ayuda en la activación del plugin
* Mejora  - Si sólo hay una agencia de envío configurada, ésta aparece ya preseleccionada en la metabox del pedido
* Fix     - Añadido el nombre del plugin al text domain para hacerlo traducible
* Fix     - Eliminada la llamada a un JavaScript inexistente en la metabox de la pantalla del pedido
* Update  - Actualizada la URL de seguimiento en la creación de la agencia por defecto SEUR
* Update  - Probado con la versión alfa de WordPress 5.2

Versión 1.2.6, 6 junio 2018
------------------------------------------------------------------------------------
* Mejora  - Añadida URL directa para el seguimiento en Correos Express

Versión 1.2.5, 17 mayo 2018
------------------------------------------------------------------------------------
* Mejora  - Añadida plantilla POT para traducciones
* Fix     - Añadido el text domain en uno de los textos, que carecía de él

Versión 1.2.4, 20 abril 2018
------------------------------------------------------------------------------------
* Mejora  - Pequeñas mejoras de optimización en el código
* Fix     - Corregido un fallo que hacía que se ocultaran los campos personalizados de los pedidos
* Update  - Plugin probado con la versión en desarrollo de WooCommerce 3.4

Versión 1.2.3, 14 marzo 2018
---------------------------------------------------------------------------------------
* Fix     - Corregido un error en el formateo de la fecha: la fecha ahora se muestra según la opción establecida para WordPress

Versión 1.2.2, 12 marzo 2018
---------------------------------------------------------------------------------------
* Mejora  - Ajuste del ancho de los campos de la metabox para mejor visibilidad
* Update  - Diversos cambios de pequeña entidad para hacer el plugin compatible con el plugin Estados de pedido personalizados con notificación (https://www.enriquejros.com/plugins/estados-pedido-notificacion-woocommerce/)

Versión 1.2.1, 20 febrero 2018
---------------------------------------------------------------------------------------
* Mejora  - Se adelanta el guardado de datos para que se incluyan en el email aunque se introduzcan los datos y se cambie el estado en una misma acción
* Mejora  - Mejor control en el establecimiento de variables
* Fix     - Solucionado un error en la sustitución del código postal en la URL de seguimiento

Versión 1.2.0, 19 febrero 2018
---------------------------------------------------------------------------------------
* Añadido - Se incluye la información de seguimiento en el email de Pedido completado

Versión 1.1.0, 17 febrero 2018
---------------------------------------------------------------------------------------
* Añadido - Posibilidad de editar las URLs de seguimiento, las agencias y de añadir o eliminar agencias

Versión 1.0.0, 16 febrero 2018
---------------------------------------------------------------------------------------
* Release - Versión inicial