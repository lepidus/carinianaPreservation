# Cariniana Preservation

[![Latest Release](https://img.shields.io/github/v/release/lepidus/carinianaPreservation)](https://github.com/lepidus/carinianaPreservation/releases)

[Português (BR)](./README.md) | [English](./README.en.md) | [Español](./README.es.md)

The Cariniana Preservation plugin facilitates the digital preservation process of OJS journals through the Cariniana Network. Its main functionality is sending an email to the Cariniana Network containing the following information about the journal to be preserved:

* Publisher / Institution
* Journal title
* ISSN
* eISSN
* Base URL
* Journal identifier
* Available years
* Issue volumes
* Notes and comments
* OJS version

In addition to this information, on the first submission the *Responsibility and Authorization Term for preservation in the Cariniana Network* filled out by the people responsible for the journal is also sent.

## Presentation Video

[![Watch the presentation video on Vimeo](https://img.shields.io/badge/Watch%20presentation%20video-Click%20here-blue?logo=vimeo)](https://vimeo.com/997938301/c62617794b)

Note: The video is available only in Portuguese (Brazil) at this time.

## Compatibility

This plugin is compatible with **OJS** versions **3.3.0**, **3.4.0**, and **3.5.0**.

### Versioning

The plugin versioning follows the OJS version compatibility pattern:

* **v1.x.x** - Compatible with OJS 3.3
* **v2.x.x** - Compatible with OJS 3.4
* **v3.x.x** - Compatible with OJS 3.5

## Installation and Configuration

1. Go to *Settings -> Website -> Plugins -> Plugin Gallery*. Click **Cariniana Preservation** and then *Install*.
2. Go to *Distribution -> Archiving*. Enable the option so LOCKSS can store and distribute the journal content. Save.
3. In the `Installed Plugins` tab under `Website Settings`, locate the "Cariniana Preservation" plugin and open its settings. In the new window, attach the Responsibility and Authorization Term filled out and signed by the person responsible for the journal.

Optionally you can provide an email which will receive copies of the information sent to the Cariniana network when the journal is submitted for preservation.

After configuration, the plugin is ready to use.

## Features

### Submission for preservation

In the `Installed Plugins` tab under `Website Settings`, find the "Cariniana Preservation" plugin.

The plugin has an option called "Submission for preservation". Clicking this option opens a window to confirm sending the email with the data.

By clicking "Submit", an email will be sent to the Cariniana Network containing as attachments the filled responsibility and authorization term, a spreadsheet with identification data of the journal content, and an XML document containing the data for inserting the journal into the Cariniana network.

If any of the data required for submission has not been previously filled in OJS, an error message will be shown.

To avoid errors, the following data must be filled in:

* Publisher
* Title
* Electronic or print ISSN
* At least one published issue
* Journal abbreviation
* Journal abstract
* Primary and Technical contacts of the journal

### Updating preserved data

For journals already submitted for preservation using the plugin, you can also send updates when data changes.

* In the submission window, a message will show the date and time of the last submission or update for preservation.
* When manually submitting the form, an email will be sent to the Cariniana Network with the updated XML containing the most recent data of the journal.
* Always respecting the required preservation data to determine the success of the send.

### Automatic update detection

When the plugin is **active** for the journal, it will automatically perform a weekly check for differences in the preserved data. If differences exist, the plugin will send an email to the Cariniana Network with the updated data.

## Automatic monitoring configuration

Automatic monitoring of updates uses the OJS Cron scheduled tasks through the Acron plugin which is installed by default in OJS 3.3.0 and 3.4.0.

To execute via Cron directly on the server you can use the command:

```bash
php tools/runScheduledTasks.php ojs/plugins/generic/carinianaPreservation/scheduledTasks.xml
```

## Using in development or test environment

* **Development installation**

Clone the repository and run `composer install` in the plugin directory.

* **Test email sending**

By default the plugin sends email to IBICT. To change the recipient email in a test environment, you need additional configuration in OJS. Add the following lines to the `config.inc.php` file:

```ini
[carinianapreservation]
email_for_tests = "your-test-email@example.org"
```

## License

![License](https://img.shields.io/github/license/lepidus/carinianaPreservation)

**License: GNU General Public License v3.0**

**Copyright: 2023-2025 Lepidus Tecnologia**
