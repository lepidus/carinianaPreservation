<?php

/**
 * @file plugins/generic/carinianaPreservation/classes/tasks/PreservationUpdateChecker.inc.php
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt.
 *
 * @class PreservationUpdateChecker
 *
 * @ingroup plugins_generic_carinianaPreservation
 *
 * @brief Scheduled task to check for XML changes and send preservation update emails to Cariniana.
 */

namespace APP\plugins\generic\carinianaPreservation\classes\tasks;

use APP\core\Application;
use APP\journal\JournalDAO;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationUpdateEmailBuilder;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlBuilder;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class PreservationUpdateChecker extends ScheduledTask
{
    /** @var CarinianaPreservationPlugin $plugin */
    public $plugin;

    public function __construct($args)
    {
        PluginRegistry::loadCategory('generic');
        $plugin = PluginRegistry::getPlugin('generic', 'carinianapreservationplugin');
        $this->plugin = $plugin;

        if (is_a($plugin, 'CarinianaPreservationPlugin')) {
            $plugin->addLocaleData();
        }

        parent::__construct($args);
    }

    public function getName()
    {
        return __('plugins.generic.carinianaPreservation.updateChecker');
    }

    public function executeActions()
    {
        if (!$this->plugin) {
            return false;
        }

        $success = true;

        $journalDao = Application::getContextDAO(); /** @var JournalDAO $journalDao */
        $journals = $journalDao->getAll(true);

        while ($journal = $journals->next()) {
            if (!$this->checkForXmlChanges($journal)) {
                $success = false;
            }
        }

        return $success;
    }

    public function checkForXmlChanges($journal)
    {
        if (!$this->plugin->getSetting($journal->getId(), 'enabled')) {
            return true;
        }

        if (!$journal->getData('enableLockss')) {
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.carinianaPreservation.updateChecker.lockssDisabled',
                    ['journalName' => $journal->getLocalizedName()]
                ),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
            return true;
        }

        if (!$this->plugin->getSetting($journal->getId(), 'lastPreservationTimestamp')) {
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.carinianaPreservation.updateChecker.notPreserved',
                    ['journalName' => $journal->getLocalizedName()]
                ),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
            return true;
        }

        $currentXmlMd5 = $this->generateCurrentXmlMd5($journal);
        $storedXmlMd5 = $this->plugin->getSetting($journal->getId(), 'preservedXMLmd5');

        if ($currentXmlMd5 !== $storedXmlMd5) {
            try {
                $this->sendUpdateEmail($journal);
                $this->addExecutionLogEntry(
                    __(
                        'plugins.generic.carinianaPreservation.updateChecker.updateEmailSent',
                        ['journalName' => $journal->getLocalizedName()]
                    ),
                    ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_COMPLETED
                );
            } catch (\Exception $e) {
                $this->addExecutionLogEntry(
                    __(
                        'plugins.generic.carinianaPreservation.updateChecker.updateEmailFailed',
                        ['journalName' => $journal->getLocalizedName(), 'error' => $e->getMessage()]
                    ),
                    ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_ERROR
                );
                return false;
            }
        } else {
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.carinianaPreservation.updateChecker.noChanges',
                    ['journalName' => $journal->getLocalizedName()]
                ),
                ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
        }

        return true;
    }

    public function generateCurrentXmlMd5($journal)
    {
        $baseUrl = Application::get()->getRequest()->getBaseUrl();

        $preservationXmlBuilder = new PreservationXmlBuilder($journal, $baseUrl);
        $journalAcronym = $journal->getLocalizedData('acronym', $journal->getPrimaryLocale());
        $xmlFilePath = "/tmp/temp_marcacoes_preservacao_{$journalAcronym}.xml";
        $preservationXmlBuilder->createPreservationXml($xmlFilePath);

        $md5Hash = md5_file($xmlFilePath);
        if (file_exists($xmlFilePath)) {
            unlink($xmlFilePath);
        }

        return $md5Hash;
    }

    public function sendUpdateEmail($journal)
    {
        $locale = $journal->getPrimaryLocale();
        $baseUrl = Application::get()->getRequest()->getBaseUrl();

        $emailBuilder = new PreservationUpdateEmailBuilder();
        $email = $emailBuilder->buildPreservationUpdateEmail($journal, $baseUrl, $locale);
        Mail::send($email);
    }
}
