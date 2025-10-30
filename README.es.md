# Preservación Cariniana

[![Latest Release](https://img.shields.io/github/v/release/lepidus/carinianaPreservation)](https://github.com/lepidus/carinianaPreservation/releases)

[Português (BR)](./README.md) | [English](./README.en.md) | [Español](./README.es.md)

El plugin Preservación Cariniana tiene como objetivo facilitar el proceso de preservación digital de revistas OJS a través de la Red Cariniana. Su funcionalidad principal es el envío de un correo electrónico a la Red Cariniana con la siguiente información de la revista a preservar:

* Editorial / Institución
* Título de la revista
* ISSN
* eISSN
* URL base
* Identificador de la revista
* Años disponibles
* Volúmenes de los números
* Notas y comentarios
* Versión de OJS

Además de esta información, en el primer envío también se envía el *Término de Responsabilidad y Autorización para preservación en la Red Cariniana*, completado por las personas responsables de la revista.

## Video de Presentación

[![Ver el video de presentación en Vimeo](https://img.shields.io/badge/Ver%20video%20de%20presentación-Clic%20aquí-blue?logo=vimeo)](https://vimeo.com/1089677111)

Nota: El video está disponible solo en portugués (Brasil) por el momento.

## Compatibilidad

Este plugin es compatible con **OJS** versiones **3.3.0** y **3.4.0**.

## Instalación y Configuración

1. Accede a *Configuración -> Sitio Web -> Plugins -> Galería de plugins*. Haz clic en **Preservación Cariniana** y luego en *Instalar*.
2. Accede a *Distribución -> Archivo*. Habilita la opción para que LOCKSS pueda almacenar y distribuir el contenido de la revista. Guarda.
3. En la pestaña `Plugins instalados` dentro de `Configuración del Sitio Web`, busca el plugin "Preservación Cariniana" y abre sus configuraciones. En la nueva ventana adjunta el Término de Responsabilidad y Autorización completado y firmado por la persona responsable de la revista.

Opcionalmente puedes informar un correo electrónico que recibirá copias de la información enviada a la red Cariniana cuando la revista sea sometida a preservación.

Tras la configuración, el plugin está listo para uso.

## Funcionalidades

### Envío para preservación

En la pestaña `Plugins instalados` dentro de `Configuración del Sitio Web`, busca el plugin "Preservación Cariniana".

El plugin tiene una opción llamada "Envío para preservación". Al hacer clic se abrirá una ventana para confirmar el envío del correo con los datos.

Al hacer clic en "Enviar", se enviará un correo a la Red Cariniana con los siguientes adjuntos: el término de responsabilidad y autorización completo, una hoja de cálculo con los datos de identificación del contenido de la revista y un documento XML con los datos para inserción de la revista en la red Cariniana.

Si alguno de los datos necesarios para el envío no fue completado previamente en OJS, se mostrará un mensaje de error.

Para evitar errores, se deben completar los siguientes datos:

* Editorial
* Título
* ISSN electrónico o impreso
* Al menos un número publicado
* Abreviatura de la revista
* Resumen de la revista
* Contactos Principal y Técnico de la revista

### Actualización de datos en preservación

Para revistas ya enviadas para preservación en la Red Cariniana utilizando el plugin, también es posible enviar actualizaciones cuando cambian los datos.

* En la ventana de envío se mostrará un mensaje con la fecha y hora del último envío o actualización para preservación.
* Al enviar manualmente el formulario se enviará un correo a la Red Cariniana con el XML actualizado con los datos más recientes de la revista.
* Siempre respetando los datos requeridos para preservación para determinar el éxito del envío.

### Detección automática de actualizaciones

Cuando el plugin está **activo** en la revista, realiza una verificación semanal automática de diferencias en los datos preservados. Si existen diferencias el plugin enviará un correo a la Red Cariniana con los datos actualizados.

## Configuración de monitoreo automático

El monitoreo automático de actualizaciones utiliza las tareas programadas Cron de OJS a través del plugin Acron que viene instalado por defecto en OJS 3.3.0 y 3.4.0.

Para ejecutar vía Cron directamente en el servidor se puede usar el comando:

```bash
php tools/runScheduledTasks.php ojs/plugins/generic/carinianaPreservation/scheduledTasks.xml
```

## Uso en desarrollo o pruebas

* **Instalación de desarrollo**

Clona el repositorio y ejecuta `composer install` en el directorio del plugin.

* **Envío de correo de pruebas**

Por defecto el plugin envía correo al IBICT. Para cambiar el destinatario en ambiente de pruebas agrega al archivo `config.inc.php`:

```ini
[carinianapreservation]
email_for_tests = "tu-correo-de-pruebas@example.org"
```

## Licencia

![License](https://img.shields.io/github/license/lepidus/carinianaPreservation)

**Licencia: Licencia Pública General GNU v3.0**

**Copyright: 2023-2025 Lepidus Tecnologia**
