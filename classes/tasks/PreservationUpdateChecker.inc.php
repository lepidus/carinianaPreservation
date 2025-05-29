<?php

/**
 * @file plugins/generic/carinianaPreservation/classes/tasks/PreservationUpdateChecker.inc.php
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt.
 *
 * @class PreservationUpdateChecker
 * @ingroup plugins_generic_carinianaPreservation
 *
 * @brief Scheduled task to check for XML changes and send preservation update emails to Cariniana.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class PreservationUpdateChecker extends ScheduledTask
{
    public $plugin;

    public function __construct($args)
    {
        PluginRegistry::loadCategory('generic');
        $this->plugin = PluginRegistry::getPlugin('generic', 'carinianapreservationplugin');
        $this->plugin->addLocaleData();

        parent::__construct($args);
    }

    public function getName()
    {
        return __('plugins.generic.carinianaPreservation.updateChecker');
    }

    public function executeActions()
    {
        $success = true;
        $journalDao = DAORegistry::getDAO('JournalDAO');
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

        if (!$this->plugin->getSetting($journal->getId(), 'lastPreservationTimestamp')) {
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.carinianaPreservation.updateChecker.notPreserved',
                    array('journalName' => $journal->getLocalizedName())
                ),
                SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
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
                        array('journalName' => $journal->getLocalizedName())
                    ),
                    SCHEDULED_TASK_MESSAGE_TYPE_COMPLETED
                );
            } catch (Exception $e) {
                $this->addExecutionLogEntry(
                    __(
                        'plugins.generic.carinianaPreservation.updateChecker.updateEmailFailed',
                        array('journalName' => $journal->getLocalizedName(), 'error' => $e->getMessage())
                    ),
                    SCHEDULED_TASK_MESSAGE_TYPE_ERROR
                );
                return false;
            }
        } else {
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.carinianaPreservation.updateChecker.noChanges',
                    array('journalName' => $journal->getLocalizedName())
                ),
                SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
        }

        return true;
    }

    public function generateCurrentXmlMd5($journal)
    {
        $this->plugin->import('classes.PreservationXmlBuilder');

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
        $this->plugin->import('classes.PreservationUpdateEmailBuilder');

        $locale = $journal->getPrimaryLocale();
        $baseUrl = Application::get()->getRequest()->getBaseUrl();

        $emailBuilder = new PreservationUpdateEmailBuilder();
        $email = $emailBuilder->buildPreservationUpdateEmail($journal, $baseUrl, $locale);
        $email->send();
    }
}
