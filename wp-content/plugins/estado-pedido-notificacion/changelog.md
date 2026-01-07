*** Estados de Pedido Personalizados ***

Versión 5.2.1, 5 agosto 2024
-------------------------------------------------------------------------------
* Update  - Soporte para WordPress 6.6
* Update  - Soporte para WooCommerce 9.2
* Update  - Actualizado ACF Pro a la versión 6.3.4

Versión 5.2.0, 15 julio 2024
-------------------------------------------------------------------------------
* Añadido - Compatibilidad con WooCommerce EU VAT Number
* Añadido - Compatibilidad con YITH WooCommerce EU VAT, OSS & IOSS Premium
* Añadido - Nuevo marcador de posición para mostrar el IVA europeo en las notificaciones
* Añadido - Nuevos iconos disponibles para los estados de pedido
* Fix     - La línea para el NIF/CIF en el bloque de datos podía incluir HTML aunque las notificaciones de WooCommerce se enviaran en texto plano
* Update  - Actualizado ACF Pro a la versión 6.3.3
* Update  - Actualizada la plantilla POT para traducciones

Versión 5.1.8, 24 junio 2024
-------------------------------------------------------------------------------
* Fix     - Se previene un aviso en el dashboard en caso de que no se detecte la pantalla
* Update  - Actualizado ACF Pro a la versión 6.3.1.2

Versión 5.1.7, 3 junio 2024
-------------------------------------------------------------------------------
* Fix     - El autoincremento de un booleano generaba un mensaje de aviso en algunas versiones de PHP
* Update  - Soporte para WooCommerce 9.0
* Update  - Actualizado ACF Pro a la versión 6.3.0.1

Versión 5.1.6, 13 mayo 2024
-------------------------------------------------------------------------------
* Añadido - Integración con los plugins Solicitar NIF y WC - APG NIF/CIF/NIE Field para mostrar el NIF en las notificaciones
* Mejora  - Mejora de la claridad en la función que genera el texto de ayuda para la plantilla
* Update  - Soporte para WooCommerce 8.9
* Update  - Actualizado ACF Pro a la versión 6.2.9
* Update  - Actualizada la plantilla POT para traducciones

Versión 5.1.5, 22 abril 2024
-------------------------------------------------------------------------------
* Añadido - Se declara WooCommerce como dependencia en la cabecera
* Update  - Soporte para WordPress 6.5
* Update  - Actualizado ACF Pro a la versión 6.2.8
* Update  - Actualizada la plantilla POT para traducciones

Versión 5.1.4, 1 abril 2024
-------------------------------------------------------------------------------
* Añadido - Nuevos iconos disponibles para los estados de pedido
* Update  - Soporte para WooCommerce 8.8

Versión 5.1.3, 11 marzo 2024
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 8.7
* Update  - Actualizado ACF Pro a la versión 6.2.7

Versión 5.1.2, 19 febrero 2024
-------------------------------------------------------------------------------
* Mejora  - Se establece el dato meta _date_completed mediante el método nativo set_date_paid
* Update  - Soporte para WooCommerce 8.6
* Update  - Actualizado ACF Pro a la versión 6.2.6.1

Versión 5.1.1, 29 enero 2024
-------------------------------------------------------------------------------
* Update  - Actualizado ACF Pro a la versión 6.2.5

Versión 5.1.0, 8 enero 2024
-------------------------------------------------------------------------------
* Mejora  - Mejoras de eficiencia en el código
* Update  - Soporte para WooCommerce 8.5

Versión 5.0.9, 10 diciembre 2023
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 8.4
* Update  - Actualizado ACF Pro a la versión 6.2.4

Versión 5.0.8, 20 noviembre 2023
-------------------------------------------------------------------------------
* Añadido - Opción para considerar los pedidos en un estado de pedido concreto como completados
* Fix     - En determinadas circunstancias podía generarse un error al crear un nuevo pedido desde el backend
* Update  - Soporte para WordPress 6.4
* Update  - Soporte para WooCommerce 8.3
* Update  - Actualizado ACF Pro a la versión 6.2.3

Versión 5.0.7, 30 octubre 2023
-------------------------------------------------------------------------------
* Mejora  - Mejoras de eficiencia en el código
* Update  - Actualizado ACF Pro a la versión 6.2.2

Versión 5.0.6, 2 octubre 2023
-------------------------------------------------------------------------------
* Añadido - Añadidos quince nuevos iconos para representar los estados de pedido personalizados, incluyendo iconos sociales
* Update  - Soporte para WooCommerce 8.2

Versión 5.0.5, 11 septiembre 2023
-------------------------------------------------------------------------------
* Añadido - Compatibilidad de las notificaciones con el filtro woocommerce_get_order_item_totals
* Update  - Soporte para WooCommerce 8.1
* Update  - Actualizado ACF Pro a la versión 6.2.1.1

Versión 5.0.4, 21 agosto 2023
-------------------------------------------------------------------------------
* Mejora  - La tabla resumen del pedido incluye un enlace al resumen del pedido en Mi cuenta para la notificación que se envía al cliente
* Mejora  - La tabla resumen del pedido incluye un enlace a la pantalla de administración del pedido para la notificación que se envía al admin
* Mejora  - Mejora de la eficiencia en la compatibilidad con plugins de numeración secuencial de pedidos
* Fix     - El estilo del bloque de dirección en la notificación podía ser diferente al resto
* Update  - Actualizado ACF Pro a la versión 6.2.0

Versión 5.0.3, 31 julio 2023
-------------------------------------------------------------------------------
* Mejora  - La variable %%instrucciones%% sólo muestra los campos de la cuenta bancaria para los que se han introducido datos
* Mejora  - La variable %%instrucciones%% puede también mostrar los datos de número de cuenta y código de clasificación
* Mejora  - Se previenen avisos en situaciones especiales al generar la notificación
* Fix     - Las instrucciones del método de pago podían mostrarse sin separación de bloque en caso de que las notificaciones estuvieran configuradas para enviarse en texto plano
* Fix     - Corregido el text domain de un texto que había quedado fuera de la traducción
* Update  - Soporte para WooCommerce 8.0
* Update  - Actualizada la plantilla POT para traducciones

Versión 5.0.2, 10 julio 2023
-------------------------------------------------------------------------------
* Fix     - La pasarela "Contra reembolso" sólo estaba disponible en la automatización si se marcaba la opción "Activar para pedidos virtuales"
* Update  - Soporte para WooCommerce 7.9
* Update  - Actualizado ACF Pro a la versión 6.1.7

Versión 5.0.1, 19 junio 2023
-------------------------------------------------------------------------------
* Fix     - Las automatizaciones generaban las notas para el pedido de forma no compatible con HPOS
* Fix     - La metabox de acciones generaba un aviso en caso de estar activado HPOS
* Fix     - El fondo de las filas en el listado de pedidos podía aparecer modificado en caso de estar activado HPOS
* Update  - Soporte para WooCommerce 7.8

Versión 5.0.0, 29 mayo 2023
-------------------------------------------------------------------------------
* Añadido - Soporte para la nueva característica HPOS de WooCommerce 8 (beta)
* Añadido - Variable %%telefono%% para mostrar el teléfono del cliente en la notificación
* Añadido - Variable %%instrucciones%% para mostrar las instrucciones del método de pago en la notificación (incluyendo el/los número/s de cuenta en caso de pago por transferencia bancaria)
* Añadido - Clases CSS específicas para los bloques de dirección en la notificación
* Añadido - Compatibilidad con WooCommerce Sequential Order Numbers Pro de SkyVerge
* Añadido - Se pueden importar los estados de pedido creados con YITH WooCommerce Custom Order Status Premium
* Mejora  - Mejora de la compatibilidad con Sequential Order Numbers for WooCommerce de SkyVerge
* Mejora  - Una vez importados todos los estados de pedido creados con otros plugins, el botón de importación desaparece
* Mejora  - No se modifican las acciones en lote disponibles para los pedidos en la papelera
* Mejora  - Mayor legibilidad en las opciones de la pestaña de características de la configuración del estado de pedido
* Mejora  - Se establece un color por defecto para los estados de pedido importados
* Mejora  - Mejoras de eficiencia en la recuperación de datos para las variables de las plantillas
* Mejora  - Mejoras de eficiencia en el init
* Fix     - El botón para importar estados de pedido creados con otros plugins podía no funcionar correctamente
* Fix     - La variable %%descargas%% no era sustituida en las notificaciones de los pedidos que no contenían archivos descargables
* Update  - Fin del soporte para WooCommerce 3.2 y anteriores
* Update  - Actualizado ACF Pro a la versión 6.1.6
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.4.1, 8 mayo 2023
-------------------------------------------------------------------------------
* Mejora  - Mejora de la eficiencia previniendo la carga de ACF en llamadas desde el frontend
* Update  - Soporte para WordPress 6.3
* Update  - Soporte para WooCommerce 7.7
* Update  - Actualizado ACF Pro a la versión 6.1.5

Versión 4.4.0, 17 abril 2023
-------------------------------------------------------------------------------
* Fix     - En determinadas ocasiones se podía duplicar la información de pago en la página de pedido recibido
* Update  - Actualizado ACF Pro a la versión 6.1.4

Versión 4.3.9, 27 marzo 2023
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 7.6

Versión 4.3.8, 6 marzo 2023
-------------------------------------------------------------------------------
* Mejora  - Mejora de la eficiencia en el modo en que el plugin recupera las pasarelas disponibles

Versión 4.3.7, 6 marzo 2023
-------------------------------------------------------------------------------
* Mejora  - Se previenen avisos por creación de propiedades dinámicas en PHP8.2+
* Update  - Soporte para WooCommerce 7.5

Versión 4.3.6, 13 febrero 2023
-------------------------------------------------------------------------------
* Mejora  - Las opciones del mensaje de ayuda en la desactivación del plugin son más claras
* Fix     - No se estaba mostrando el mensaje de ayuda en la desactivación del plugin (definitivo)
* Update  - Soporte para WooCommerce 7.4
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.3.5, 23 enero 2023
-------------------------------------------------------------------------------
* Fix     - No se estaba mostrando el mensaje de ayuda en la desactivación del plugin
* Update  - Actualizado ACF Pro a la versión 6.0.7
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.3.4, 2 enero 2023
-------------------------------------------------------------------------------
* Añadido - Compatibilidad con Sequential Order Numbers for WooCommerce de WebToffee
* Update  - La versión mínima requerida de PHP es la 7.3
* Update  - Soporte para WooCommerce 7.3
* Update  - Actualizado ACF Pro a la versión 6.0.6

Versión 4.3.3, 12 diciembre 2022
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 7.2
* Update  - Actualizado ACF Pro a la versión 6.0.5

Versión 4.3.2, 21 noviembre 2022
-------------------------------------------------------------------------------
* Update  - Soporte para WordPress 6.2
* Update  - Actualizado ACF Pro a la versión 6.0.4

Versión 4.3.1, 31 octubre 2022
-------------------------------------------------------------------------------
* Mejora  - Se reduce el número de llamadas API HTTPS
* Update  - Soporte para WooCommerce 7.1
* Update  - Actualizado ACF Pro a la versión 6.0.3

Versión 4.3.0, 10 octubre 2022
-------------------------------------------------------------------------------
* Fix     - Añadido el text domain a un string que había quedado fuera de la traducción
* Update  - Soporte para WooCommerce 7.0
* Update  - Actualizado ACF Pro a la versión 6.0.2
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.2.9, 19 septiembre 2022
-------------------------------------------------------------------------------
* Añadido - Añadidos doce nuevos iconos para representar los estados de pedido personalizados

Versión 4.2.8, 29 agosto 2022
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 6.9

Versión 4.2.7, 1 agosto 2022
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 6.8
* Update  - Actualizado ACF Pro a la versión 5.12.3

Versión 4.2.6, 11 julio 2022
-------------------------------------------------------------------------------
* Mejora  - El timeout de conexión para actualizaciones se reduce a 10 segundos
* Update  - Soporte para WooCommerce 6.7

Versión 4.2.5, 20 junio 2022
-------------------------------------------------------------------------------
* Añadido - Variable %%apellidos%% para incluir los apellidos de la dirección de facturación en la notificación
* Añadido - Variable %%empresa%% para incluir la empresa en la notificación
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.2.4, 30 mayo 2022
-------------------------------------------------------------------------------
* Añadido - Filtro estados_pedido_carga_automatizaciones para deshabilitar las opciones de automatización de cambio de estado
* Update  - Soporte para WordPress 6.1
* Update  - Soporte para WooCommerce 6.6

Versión 4.2.3, 9 mayo 2022
-------------------------------------------------------------------------------
* Mejora  - Mejoras de eficiencia
* Fix     - En determinadas circunstancias no se habilitaba la descarga de archivos descargables en los estados de pedido configurados para permitirla
* Update  - Soporte para WooCommerce 6.5

Versión 4.2.2, 18 abril 2022
-------------------------------------------------------------------------------
* Mejora  - Mejoras de eficiencia
* Update  - Actualizado ACF Pro a la versión 5.12.2

Versión 4.2.1, 28 marzo 2022
-------------------------------------------------------------------------------
* Añadido - Campo para la clave de licencia en la pestaña Integración de los ajustes de WooCommerce
* Update  - Soporte para WooCommerce 6.4
* Update  - Actualizado ACF Pro a la versión 5.12.1
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.2.0, 7 marzo 2022
-------------------------------------------------------------------------------
* Añadido - Nueva variable %%notas%% para mostrar las notas del pedido en la notificación
* Update  - Soporte para WooCommerce 6.3
* Update  - Actualizado ACF Pro a la versión 5.12
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.1.1, 14 febrero 2022
-------------------------------------------------------------------------------
* Mejora  - Mejoras de eficiencia
* Update  - Soporte para WordPress 6.0
* Update  - Soporte para WooCommerce 6.2

Versión 4.1.0, 17 enero 2022
-------------------------------------------------------------------------------
* Fix     - El cuadro para introducir una nueva licencia no aparecía en determinados casos cuando la licencia anterior no era válida
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.0.9, 30 diciembre 2021
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 6.1
* Update  - Actualizado ACF Pro a la versión 5.11.4

Versión 4.0.8, 30 noviembre 2021
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 6.0
* Update  - Actualizado ACF Pro a la versión 5.11.3

Versión 4.0.7, 25 octubre 2021
-------------------------------------------------------------------------------
* Fix     - Corregida una llamada al constructor de un método parent
* Update  - Soporte para WooCommerce 5.9

Versión 4.0.6, 4 octubre 2021
-------------------------------------------------------------------------------
* Fix     - Corregido un error en un snippet de ejemplo
* Update  - Soporte para WooCommerce 5.8
* Update  - Actualizado ACF Pro a la versión 5.10.2

Versión 4.0.5, 6 septiembre 2021
-------------------------------------------------------------------------------
* Update  - Soporte para WooCommerce 5.7
* Update  - Actualizado ACF Pro a la versión 5.10.1

Versión 4.0.4, 16 agosto 2021
-------------------------------------------------------------------------------
* Añadido - Se incluye configuración por defecto para los campos personalizados en WPML
* Update  - Soporte para WooCommerce 5.6

Versión 4.0.3, 26 julio 2021
-------------------------------------------------------------------------------
* Update  - Soporte para WordPress 5.9
* Update  - Soporte para WooCommerce 5.5
* Update  - Actualizado ACF Pro a la versión 5.9.9

Versión 4.0.2, 5 julio 2021
-------------------------------------------------------------------------------
* Mejora  - Al aplicar una automatización, la nota creada en el pedido indica el tipo de la misma (por método de pago o por rol de usuario)
* Mejora  - Mejoras en la eficiencia del código
* Fix     - Los estados ya no aparecen como opciones para los menús de navegación
* Update  - Actualizada la plantilla POT para traducciones

Versión 4.0.1, 14 junio 2021
-------------------------------------------------------------------------------
* Añadido - Soporte para la opción de contenido adicional de los ajustes de correo electrónico de WooCommerce
* Mejora  - Mejoras en la seguridad del código
* Fix     - Se previene un aviso en determinadas circunstancias al comprobar la licencia
* Update  - Soporte para WooCommerce 5.4
* Update  - Actualizado ACF Pro a la versión 5.9.6

Versión 4.0.0, 24 mayo 2021
-------------------------------------------------------------------------------
* Añadido - Soporte para escritura RTL
* Añadido - Opción para permitir la descarga de los archivos descargables de un pedido en el estado personalizado
* Añadido - Nueva variable %%descargas%% para insertar en la notificación la tabla de archivos descargables del pedido si los hay
* Añadido - Nueva variable %%fecha_pedido%% para insertar en la notificación la fecha de creación del pedido
* Añadido - Filtro estados_pedido_titulo_tabla para modificar el título de la sección de tabla resumen del pedido de las notificaciones
* Añadido - Filtro estados_pedido_titulo_descargas para modificar el título de la sección de enlaces de descargas de las notificaciones
* Añadido - Soporte para el hook woocommerce_email_footer_text en las notificaciones en texto plano
* Añadido - Soporte para los marcadores de posición establecidos por WooCommerce
* Añadido - Se añade una nota al pedido cuando se envían manualmente las notificaciones de un estado personalizado
* Añadido - Mensaje de ayuda en los pedidos en un estado que no permite la edición de los mismos
* Añadido - Ejemplo de ayuda para la opción de estilos personalizados de la notificación
* Mejora  - Se da preferencia al encabezado de correo electrónico establecido en la configuración de WooCommerce
* Mejora  - Si no se han establecido datos diferentes para el envío, la variable %%datos_envio%% muestra los datos de facturación
* Mejora  - Mejoras en la seguridad del código
* Mejora  - Mejoras en la eficiencia del código
* Fix     - Corregido un error en un texto de ayuda
* Fix     - Añadido el text domain a dos cadenas que quedaban fuera de la traducción
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.4.0, 10 mayo 2021
-------------------------------------------------------------------------------
* Añadido - Posibilidad de establecer CSS personalizado para las notificaciones
* Mejora  - Verificación SSL en la comprobación de actualizaciones
* Mejora  - Mejoras de eficiencia en el init
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.3.4, 26 abril 2021
-------------------------------------------------------------------------------
* Añadido - Filtro estados_pedido_oculta_procesando para ocultar el botón de acción del estado por defecto Procesando
* Añadido - Filtro estados_pedido_oculta_completado para ocultar el botón de acción del estado por defecto Completado
* Update  - Soporte para WooCommerce 5.3
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.3.3, 12 abril 2021
-------------------------------------------------------------------------------
* Update  - Soporte para WordPress 5.8
* Update  - Actualizada la librería para avisos a SweetAlert2

Versión 3.3.2, 24 marzo 2021
-------------------------------------------------------------------------------
* Añadido - Texto de ayuda para la opción de Siguiente estado en el flujo de pedidos
* Mejora  - No se muestra botón/enlace de seguimiento si no se ha establecido la URL de seguimiento para la agencia
* Fix     - El color del texto en las notificaciones podía no coincidir con el establecido en los ajustes de WooCommerce en determinadas configuraciones
* Fix     - Solucionado un aviso que podía mostrarse en la metabox de acciones del pedido al crear un pedido desde el área de administración
* Update  - Soporte para WooCommerce 5.2
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.3.1, 15 marzo 2021
-------------------------------------------------------------------------------
* Mejora  - Si se incluyen en la notificación las variables de seguimiento pero éstas no se han establecido en los datos en el pedido, se muestra el texto [Consultar]
* Fix     - Las variables de envío no se asignaban correctamente cuando los pedidos se cambiaban de estado en lote
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.3.0, 4 marzo 2021
-------------------------------------------------------------------------------
* Añadido - Automatizaciones: es posible poner en un determinado estado personalizado automáticamente tras su creación los pedidos de usuarios pertenecientes a los roles seleccionados
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.2.1, 18 febrero 2021
-------------------------------------------------------------------------------
* Mejora  - Integración de los editores de notificación y de texto de seguimiento
* Mejora  - La fecha de envío se muestra en el formato establecido en los ajustes de WordPress
* Fix     - Los campos para el seguimiento en la notificación aparecían aunque no estuviera activado el plugin de seguimientos
* Update  - Soporte para WooCommerce 5.1
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.2.0, 13 febrero 2021
-------------------------------------------------------------------------------
* Añadido - Variable %%datos_envio%% para mostrar los datos de envío en las notificaciones
* Añadido - Variable %%datos_facturacion%% para mostrar los datos de facturación en las notificaciones
* Añadido - Variable %%metodo_envio%% para mostrar el método de envío del pedido en las notificaciones
* Añadido - Variable %%metodo_pago%% para mostrar el método de pago del pedido en las notificaciones
* Update  - Actualizado ACF Pro a la versión 5.9.5
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.1.3, 25 enero 2021
-------------------------------------------------------------------------------
* Añadido - Opción para hacer que los pedidos que estén en el estado personalizado se puedan editar
* Añadido - Cabecera de versión mínima de PHP requerida
* Mejora  - Mejoras de eficiencia en el init
* Update  - Soporte para WooCommerce 5.0
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.1.2, 20 enero 2021
-------------------------------------------------------------------------------
* Añadido - Plantilla para personalizar el texto con la información de seguimiento del pedido en la notificación cuando se está usando el plugin de seguimientos (https://www.enriquejros.com/plugins/seguimiento-envios-woocommerce/)
* Mejora  - Mejora de la eficiencia en la recuperación de datos de seguimiento
* Mejora  - La lista de estados de pedido muestra las direcciones de administración que recibirán la notificación de dicho estado
* Mejora  - Mejora de la organización del código de los campos personalizados de la configuración de estados de pedido
* Fix     - Se previene un error al disparar las notificaciones manualmente en determinadas circunstancias
* Update  - Se aumenta el margen de tiempo por defecto durante el que se ejecuta la automatización a 300 segundos
* Update  - Actualizado ACF Pro a la versión 5.9.4
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.1.1, 14 enero 2021
-------------------------------------------------------------------------------
* Añadido - Compatibilidad completa con notificaciones en texto plano
* Añadido - Filtro estados_pedido_tiempo_auto para modificar el tiempo máximo de comprobación de la automatización
* Mejora  - Se mejora la seguridad en las automatizaciones limitando el tiempo en que es posible ejecutarlas
* Mejora  - El texto con la información de seguimiento del pedido ahora está incluido en el text domain del plugin
* Fix     - Se previene un error fatal durante el envío de las notificaciones en presencia de plugins que usaran datos concretos del filtro woocommerce_email_order_items_args
* Update  - Fin del soporte para WooCommerce 2.6 y anteriores
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.1.0, 11 enero 2021
-------------------------------------------------------------------------------
* Añadido - Compatibilidad de la variable %%tabla%% con el filtro woocommerce_email_order_items_args
* Añadido - Se añade una nota a las notas del pedido cuando se cambia el estado mediante automatización
* Añadido - Enlace en la metabox de información del estado de pedido al listado de pedidos en ese estado
* Añadido - Enlace en el resumen del widget de escritorio al listado de pedidos en ese estado
* Añadido - Mensajes específicos para los cambios en lote sobre estados de pedido personalizados
* Mejora  - Pantalla de configuración del estado de pedido organizada en pestañas
* Mejora  - Se pasa al filtro estados_pedido_variables_email un booleano indicando si el correo es enviado en texto plano
* Mejora  - Comprobaciones redundantes antes de llevar a cabo un cambio de estado mediante automatización
* Mejora  - Mejora de la eficiencia en el dashboard
* Fix     - Se previene un error que podía producirse en determinadas circunstancias al cambiar un pedido a un estado personalizado
* Fix     - Se previene un error en la página de edición del estado de pedido y/o en el widget de dashboard cuando el número de pedidos en un estado es muy elevado
* Fix     - Solucionado un aviso que podía mostrarse en la página de confirmación del pedido si no se habían establecido automatizaciones en estados de pedido preexistentes
* Fix     - Se elimina una propiedad sin usar en la clase de arranque del plugin
* Update  - Actualizada la plantilla POT para traducciones

Versión 3.0.0, 22 diciembre 2020
-------------------------------------------------------------------------------
* Añadido - Automatizaciones: es posible poner en un determinado estado personalizado automáticamente tras su creación los pedidos pagados mediante el/los método/s de pago seleccionado/s
* Añadido - Registro y envío de las notificaciones de estados mediante el sistema nativo de correos electrónicos de WooCommerce
* Añadido - Compatibilidad con WooCommerce PDF Invoices & Packing Slips (genera/adjunta factura automáticamente a la notificación al pasar el pedido a un estado personalizado)
* Añadido - Nuevo campo en los ajustes del estado de pedido para el encabezamiento de las notificaciones
* Añadido - Posibilidad de enviar las notificaciones del estado de pedido sólo de forma manual
* Añadido - Nueva variable %%tabla%% para insertar la tabla de resumen del pedido en las notificaciones
* Añadido - Compatibilidad de las notificaciones con el hook woocommerce_email_header de WooCommerce
* Añadido - Compatibilidad de las notificaciones con el hook woocommerce_email_before_order_table de WooCommerce
* Añadido - Compatibilidad de las notificaciones con el hook woocommerce_email_after_order_table de WooCommerce
* Añadido - Compatibilidad de las notificaciones con el hook woocommerce_email_footer de WooCommerce
* Añadido - Soporte para notificaciones en texto plano
* Añadido - Mensajes específicos para los cambios sobre estados de pedido personalizados
* Mejora  - Mejora de la eficiencia mediante el uso de la caché de objetos para la query de recuperación de estados personalizados
* Mejora  - Nuevos colores por defecto para los estados de pedido personalizados, con mayor contraste en la lista de pedidos
* Fix     - Compatibilidad del envío de la notificación de procesando con plugins de numeración secuencial de pedidos
* Fix     - Incidencia con los botones de acción en presencia de YITH WooCommerce Sequential Order Number si se usaba un prefijo o sufijo para el número de pedido
* Fix     - El widget de escritorio de WooCommerce podía generarse con una visualización deficiente si se mostraba un número impar de estados de pedido y era a la vez usado por otros plugins
* Fix     - El selector de siguiente estado en el flujo de pedidos mostraba opciones en blanco en caso de que hubiera estados de pedido personalizados a los que no se hubiera asignado un nombre
* Fix     - El widget de escritorio no mostraba ningún nombre para el estado en caso de que a éste no se le hubiera asignado un nombre
* Fix     - No era visible el título de la metabox de información del estado de pedido personalizado
* Fix     - Se tiene en cuenta el caso de activación tardía de la licencia
* Update  - Soporte para WooCommerce 4.9
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.7.3, 17 diciembre 2020
-------------------------------------------------------------------------------
* Bump version

Versión 2.7.2, 17 diciembre 2020
-------------------------------------------------------------------------------
* Añadido - Filtro ejr_acf_lite para deshabilitar la constante ACF_LITE definida por el plugin
* Update  - Soporte para WordPress 5.7

Versión 2.7.1, 6 diciembre 2020
-------------------------------------------------------------------------------
* Mejora - Se previene la colisión de nombres de acciones AJAX
* Fix    - Se elimina una constante sin utilizar

Versión 2.7.0, 6 diciembre 2020
-------------------------------------------------------------------------------
* Añadido - Se ignora el nuevo estado de pedido de WooCommerce 4.8 checkout-draft a la hora de detectar estados de pedido personalizados para importar
* Añadido - Se incluye el archivo loco.xml para la configuración automática en Loco Translate
* Mejora  - Mejora de la eficiencia y la seguridad durante el guardado de estados de pedido personalizados
* Mejora  - La opción para la licencia deja de estar en la sección de integraciones de WooCommerce y se traslada al listado de estados de pedido
* Mejora  - Mejoras de eficiencia en el init
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.6.0, 23 noviembre 2020
-------------------------------------------------------------------------------
* Añadido - Opción para mostrar un resumen de los pedidos que están en un estado personalizado determinado en el widget de escritorio de WooCommerce
* Añadido - Se ejecuta el hook 'estado_personalizado_{ID estado}' al cambiar un pedido a un estado personalizado, pasando como argumento el ID de pedido
* Añadido - Metabox en la pantalla de edición del estado de pedido con información sobre el mismo (número de pedidos en ese estado, ID del estado, hook de acción que le corresponde y ejemplo de uso)
* Update  - Soporte para WooCommerce 4.8
* Update  - Actualizado ACF Pro a la versión 5.9.3
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.5.5, 28 octubre 2020
-------------------------------------------------------------------------------
* Update - Soporte para WooCommerce 4.7

Versión 2.5.4, 8 octubre 2020
-------------------------------------------------------------------------------
* Fix    - En determinadas circunstancias, la notificación estándar al cambiar el estado a procesando podía no generarse si el pedido venía de un estado personalizado
* Update - Soporte para WordPress 5.6
* Update - Soporte para WooCommerce 4.6

Versión 2.5.3, 9 septiembre 2020
-------------------------------------------------------------------------------
* Update - Actualizado ACF Pro a la versión 5.9.1
* Update - Plugin probado con WordPress 5.6-alpha
* Update - Soporte para WooCommerce 4.5

Versión 2.5.2, 30 julio 2020
-------------------------------------------------------------------------------
* Update - Plugin probado con WordPress 5.5-beta3
* Update - Plugin probado con WooCommerce 4.4.0-beta

Versión 2.5.1, 21 de julio 2020
-------------------------------------------------------------------------------
* Fix     - El mensaje de ayuda en la activación sólo se muestra si WooCommerce está activo
* Update  - Plugin probado con WooCommerce 4.3.0-RC3

Versión 2.5.0, 4 de julio 2020
-------------------------------------------------------------------------------
* Añadido - Compatibilidad con Advanced Custom Fields
* Mejora  - El formato de las notificaciones se genera con los métodos provistos por WooCommerce
* Update  - Plugin probado con WooCommerce 4.3.0-RC2
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.4.0, 26 de junio 2020
-------------------------------------------------------------------------------
* Añadido - Enlace al changelog completo en los enlaces meta del plugin
* Mejora  - Se cambia el sistema de actualizaciones
* Mejora  - Mejora de la eficiencia en el admin
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.3.2, 24 de junio 2020
-------------------------------------------------------------------------------
* Mejora - Mejora de la eficiencia en el admin
* Update - Plugin probado con WooCommerce 4.3.0-RC1

Versión 2.3.1, 22 de junio 2020
-------------------------------------------------------------------------------
* Update - Actualizado ACF Pro a la versión 5.8.12
* Update - Plugin probado con WordPress 5.4.3-alpha
* Update - Plugin probado con WooCommerce 4.3.0-beta

Versión 2.3.0, 1 junio 2020
-------------------------------------------------------------------------------
* Añadido - Compatibilidad con el plugin WooCommerce Sequential Order Numbers
* Añadido - Compatibilidad con el plugin YITH WooCommerce Sequential Order Number
* Añadido - Mensaje de ayuda para la activación en las pantallas de plugins y dashboard en caso de no haber introducido la clave de licencia
* Fix     - Corregido un fallo por el cual las devoluciones no se estaban teniendo en cuenta en los informes de WooCommerce
* Fix     - Corregido un fallo por el cual el texto de la acción del pedido para reenviar las notificaciones no aparecía si el nombre del estado contenía caracteres extendidos
* Fix     - Corregido un fallo por el cual los caracteres extendidos aparecían malformados en las acciones en lote de los pedidos
* Fix     - No se estaba mostrando el mensaje de ayuda correcto en la primera activación
* Update  - Actualizada la plantilla POT para traducciones

Versión 2.2.4, 16 mayo 2020
-------------------------------------------------------------------------------
* Mejora - Se mejora la eficiencia en el init
* Update - Actualizado ACF Pro a la versión 5.8.11
* Update - Actualizada la librería Puc a la versión 4.9
* Update - Plugin probado con WooCommerce 4.2.0-beta

Versión 2.2.3, 11 mayo 2020
-------------------------------------------------------------------------------
* Añadido - Opción para establecer una o varias direcciones de correo electrónico que recibirán las notificaciones al administrador
* Update  - Actualizada plantilla de traducciones POT
* Update  - Plugin probado con WordPress 5.4.2-alpha

Versión 2.2.2, 4 mayo 2020
-------------------------------------------------------------------------------
* Fix    - Se solucionan varios avisos generados en la página de informes de WooCommerce
* Update - Plugin probado con WooCommerce 4.1.0-RC2

Versión 2.2.1, 27 abril 2020
-------------------------------------------------------------------------------
* Mejora - Mejoras de eficiencia en el código
* Fix    - Se previenen diversos avisos en caso de que no se pueda alcanzar el servidor de actualizaciones
* Update - Plugin probado con WooCommerce 4.1.0-RC1

Versión 2.2.0, 20 abril 2020
-------------------------------------------------------------------------------
* Añadido - Botón para importar los estados de pedido personalizados creados por WooCommerce Order Status Manager
* Añadido - Se muestra un aviso al desactivar el plugin advirtiendo sobre los posibles pedidos que estén en algún estado personalizado
* Mejora  - Se reduce la carga de estilos innecesarios en la página de administración de estados de pedido
* Mejora  - El slug de un estado de pedido sólo se cambia si contiene más de un guion
* Mejora  - La fecha de expiración de la licencia se muestra en un formato más amigable
* Mejora  - Mejoras de eficiencia en el código
* Fix     - Solucionado un fallo por el cual no se mostraba el tooltip sobre el botón de acción cuando el nombre de estado contenía caracteres extendidos
* Fix     - Solucionado un fallo por el cual un estado seguía mostrando archivos adjuntos tras eliminar éstos
* Update  - Plugin probado con WooCommerce 4.1.0-beta
* Update  - Actualizada la plantilla de traducciones
* Update  - Se modifica la estructura de directorios del plugin

Versión 2.1.1, 13 abril 2020
-------------------------------------------------------------------------------
* Añadido - Se muestra un mensaje de confirmación tras los cambios de estado en lote
* Añadido - Se incluye la plantilla POT para traducciones
* Mejora  - Las acciones en lote se manejan ahora mediante el modo nativo incluido a partir de WordPress 4.7
* Mejora  - Si no se establece un nombre para el estado de pedido, se muestra uno genérico en la pantalla de gestión de estados
* Mejora  - Se sustituyen los símbolos de moneda en la plantilla de notificaciones de forma más eficiente

Versión 2.1.0, 6 abril 2020
-------------------------------------------------------------------------------
* Añadido - Se pueden asignar iconos a los estados de pedido personalizados
* Añadido - Se muestra el icono del estado de pedido en la pantalla de estados personalizados
* Añadido - Se muestra el icono del estado de pedido en el botón de acción
* Añadido - Disponible una nueva variable %%total%% en la plantilla de notificaciones para incluir el total del pedido
* Mejora  - Mejora de la eficiencia en el envío de las notificaciones
* Mejora  - Se declara expresamente el alcance de todos los métodos
* Fix     - Solucionado un fallo por el que el siguiente estado en el flujo de pedidos no aparecía el primero en los botones de acción
* Fix     - Solucionado un error 404 en la ruta de un archivo CSS en la pantalla de administración de los estados de pedido
* Update  - Plugin probado con WordPress 5.4.1-alpha

Versión 2.0.0, 26 marzo 2020
-------------------------------------------------------------------------------
* Añadido - Los estados de pedido personalizados se pueden reordenar arrastrando y soltando para una gestión más fácil
* Añadido - Campo para seleccionar el siguiente estado en el flujo de pedidos
* Añadido - Se muestran los archivos adjuntos a las notificaciones en la pantalla de listado de estados de pedido
* Añadido - Integración con EDD Software Licensing
* Mejora  - Las acciones de pedido en la pantalla de lista de pedidos muestran en primer lugar el botón para cambiar el estado al siguiente estado en el flujo de pedidos
* Update  - Actualizado ACF Pro a la versión 5.8.9
* Update  - Plugin probado con WordPress 5.4-RC4
* Update  - Plugin probado con WooCommerce 4.0.1

Versión 1.6.1, 24 febrero 2020
-------------------------------------------------------------------------------
* Añadido - Filter hook estados_pedido_variables_email para personalizar las variables de la plantilla
* Fix     - Se sustituye en el correo automático la nueva variable de WooCommerce
* Update  - Plugin probado con WooCommerce 4.0.0-beta

Versión 1.6.0, 16 enero 2020
-------------------------------------------------------------------------------
* Mejora - Se integra la interfaz dentro del menú de WooCommerce
* Mejora - Se declara expresamente el alcance de todos los métodos
* Fix    - Solucionado un error que impedía que se mostrara el nombre del estado en el selector de acciones del pedido
* Fix    - Solucionado un error que hacía que, en determinadas configuraciones, no se enviaran las notificaciones
* Update - Plugin probado con WordPress 5.3.3-alpha
* Update - Plugin probado con WooCommerce 3.8.0-beta
* Update - Actualizado ACF Pro a la versión 5.8.7

Versión 1.5.1, 8 octubre 2019
-------------------------------------------------------------------------------
* Añadido - Se incluye la plantilla .POT de traducción
* Mejora  - Se establece un nombre de estado por defecto si no se rellena el título del estado personalizado
* Mejora  - Se utiliza la función más eficiente determine_locale para determinar el idioma si se está usando WordPress 5.0+
* Mejora  - Mejora de eficiencia en los métodos de arranque del plugin
* Update  - Plugin probado con WordPress 5.2.4-alpha
* Update  - Plugin probado con WooCommerce 3.8.0-beta
* Update  - Actualizado ACF Pro a la versión 5.8.4

Versión 1.5.0, 3 mayo 2019
-------------------------------------------------------------------------------
* Añadido - Ahora se pueden adjuntar archivos a las notificaciones de los estados de pedido personalizados
* Mejora  - La acción para poner un pedido en un estado personalizado sólo se muestra si el pedido no tiene ya ese estado
* Update  - Actualizado ACF Pro a la versión 5.7.13
* Update  - Actualizada la librería Puc para actualizaciones a la versión 4.6
* Update  - Plugin probado con la versión beta de WordPress 5.2
* Update  - Declarada la compatibilidad con WooCommerce 3.6

Versión 1.4.2, 21 enero 2019
-------------------------------------------------------------------------------
* Mejora - Añadido un mensaje de ayuda en la activación del plugin
* Mejora - Los botones de acciones se añaden condicionalmente teniendo en cuenta el estado actual del pedido
* Fix    - Compatibilidad con la variable site_title de WooCommerce
* Fix    - Añadido el nombre del plugin a los textos traducibles

Versión 1.4.1, 21 diciembre 2018
-------------------------------------------------------------------------------
* Mejora - Establecido un patrón singleton en la llamada al plugin
* Update - Actualizado ACF Pro a la versión 5.7.9
* Fix    - Añadida a los textos traducibles una cadena que había quedado fuera

Versión 1.4.0, 27 septiembre 2018
-------------------------------------------------------------------------------
* Mejora  - Se evita que se duplique la cabecera del email de notificación cuando se usa el plugin Email Templates
* Mejora  - Mejora de la seguridad impidiendo el acceso directo al código en todos los archivos
* Añadido - Nueva variable para incluir en la plantilla la dirección de email del cliente y actualizado el texto de ayuda
* Update  - Actualizado ACF Pro a la versión 5.6.10
* Update  - Actualizada la librería Puc para actualizaciones a la versión 4.4

Versión 1.3.5, 19 abril 2018
-------------------------------------------------------------------------------
* Fix    - Corregido un fallo que hacía que se ocultaran los campos personalizados de los pedidos
* Update - Plugin probado con la versión en desarrollo de WooCommerce 3.4

Versión 1.3.4, 23 marzo 2018
-------------------------------------------------------------------------------
* Mejora - Se previene un error debido a conflictos con los slugs de los estados de pedido
* Fix    - El campo de color del estado de pedido, que era obligatorio por error, deja de serlo
* Fix    - Corregido un texto sin traducción en las notas del pedido al cambiar el estado mediante las acciones en lote

Versión 1.3.3, 12 marzo 2018
-------------------------------------------------------------------------------
* Update - Sustituidas propiedades por métodos para acceder a los datos de nombre y correo electrónico del usuario
* Mejora - Cambios para hacer el plugin compatible con el plugin Seguimiento de envíos en los pedidos de WooCommerce (https://www.enriquejros.com/plugins/seguimiento-envios-woocommerce/)

Versión 1.3.2, 21 febrero 2018
-------------------------------------------------------------------------------
* Bump version

Versión 1.3.1, 21 febrero 2018
-------------------------------------------------------------------------------
* Añadido - Nueva opción para seleccionar el color del texto en WooCommerce 3.3 o superior
* Mejora  - Eliminada la opción "Ver" en los enlaces de acción de la pantalla de administración de estados de pedido
* Mejora  - Deshabilitada también la opción de "Edición rápida" en los enlaces de acción para prevenir la edición del slug del estado de pedido
* Fix     - Solucionado un bug que impedía la visualización del nuevo estado cuando el slug del estado de pedido contenía más de dos guiones

Versión 1.3.0, 15 febrero 2018
-------------------------------------------------------------------------------
* Mejora - Cambio del editor para la plantilla de correo electrónico de texto plano a uno WYSIWYG
* Mejora - Las notificaciones utilizan las opciones de correo electrónico establecidas en los ajustes de WooCommerce:
	* Nombre 'Desde'
	* Dirección 'Desde'
	* Imagen de cabecera
	* Texto de pie de página
	* Colores y plantilla estándar
* Update - Actualización de versión de ACF Pro

Versión 1.2.1, 5 febrero 2018
-------------------------------------------------------------------------------
* Mejora - Se establece el nombre From por defecto al nombre de la web
* Fix    - Corregido un error que se producía al reenviar las notificaciones desde las acciones del pedido

Versión 1.2.0, 29 enero 2018
-------------------------------------------------------------------------------
* Mejora - Mejoras de eficiencia en el código
* Update - Estilos adaptados a los nuevos estados de pedido de WooCommerce 3.3

Versión 1.1.0, 28 diciembre 2017
-------------------------------------------------------------------------------
* Añadido - Opción de incluir los estados de pedido en los informes de WooCommerce

Versión 1.0.1, 26 diciembre 2017
-------------------------------------------------------------------------------
* Fix - Corregido un error que hacía que el email se enviara en texto plano, aun conteniendo HTML

Versión 1.0.0, 20 octubre 2017
-------------------------------------------------------------------------------
* Release - Versión inicial