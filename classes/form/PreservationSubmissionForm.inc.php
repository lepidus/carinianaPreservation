<?php

/**
 * @file PreservationSubmissionForm.inc.php
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreservationSubmissionForm
 * @ingroup plugins_generic_carinianaPreservation
 *
 * @brief Form to submit a journal to digital preservation by Cariniana
 */

import('lib.pkp.classes.form.Form');

class PreservationSubmissionForm extends Form
{
    public $plugin;
    public $contextId;

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
        parent::__construct($plugin->getTemplateResource('preservationSubmission.tpl'));
    }

    public function readInputData()
    {
        $userVars = [];
        $lastPreservationTimestamp = $this->plugin->getSetting($this->contextId, 'lastPreservationTimestamp');

        if (!$lastPreservationTimestamp) {
            $userVars[] = 'notesAndComments';
        }

        $this->readUserVars($userVars);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $emailCopies = $this->getPreservationEmailCopies();
        $lastPreservationTimestamp = $this->plugin->getSetting($this->contextId, 'lastPreservationTimestamp');

        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'applicationName' => Application::get()->getName(),
            'emailCopies' => $emailCopies,
            'lastPreservationTimestamp' => $lastPreservationTimestamp
        ]);

        return parent::fetch($request, $template, $display);
    }

    private function getPreservationEmailCopies()
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journal = $journalDao->getById($this->contextId);
        $contactEmail = $journal->getData('contactEmail');
        $extraCopyEmail = $this->plugin->getSetting($journal->getId(), 'extraCopyEmail');

        return (empty($extraCopyEmail) ? $contactEmail : implode(', ', [$contactEmail, $extraCopyEmail]));
    }

    public function validate($callHooks = true)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journal = $journalDao->getById($this->contextId);

        $missingRequirements = $this->getMissingRequirements($journal);
        if (!empty($missingRequirements)) {
            $missingRequirementsStr = implode(', ', $missingRequirements);
            $this->addError('preservationSubmission', __(
                "plugins.generic.carinianaPreservation.preservationSubmission.missingRequirements",
                ['missingRequirements' => $missingRequirementsStr]
            ));
        }

        $statementFile = $this->plugin->getSetting($this->contextId, 'statementFile');
        if (empty($statementFile)) {
            $this->addError('preservationSubmission', __(
                "plugins.generic.carinianaPreservation.preservationSubmission.missingResponsabilityStatement"
            ));
        }

        return parent::validate($callHooks);
    }

    private function getMissingRequirements($journal)
    {
        \AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_ADMIN,
            LOCALE_COMPONENT_APP_EDITOR
        );

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issues = $issueDao->getPublishedIssues($journal->getId())->toArray();

        $requirements = [
            'manager.setup.publisher' => $journal->getData('publisherInstitution'),
            'manager.setup.contextTitle' => $journal->getLocalizedData('name'),
            'journal.issn' => $journal->getData('printIssn') ?? $journal->getData('onlineIssn'),
            'context.path' => $journal->getData('urlPath'),
            'manager.setup.contextInitials' => $journal->getLocalizedData('acronym'),
            'admin.settings.contactEmail' => $journal->getData('contactEmail'),
            'editor.publishedIssues' => $issues
        ];

        $missingRequirements = [];
        foreach ($requirements as $name => $value) {
            if (empty($value)) {
                $missingRequirements[] = __($name);
            }
        }

        return $missingRequirements;
    }

    public function execute(...$functionArgs)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');

        $locale = AppLocale::getLocale();
        $journal = $journalDao->getById($this->contextId);
        $baseUrl = Application::get()->getRequest()->getBaseUrl();

        if ($this->plugin->getSetting($this->contextId, 'lastPreservationTimestamp')) {
            import('plugins.generic.carinianaPreservation.classes.PreservationUpdateEmailBuilder');
            $emailBuilder = new PreservationUpdateEmailBuilder();
            $email = $emailBuilder->buildPreservationUpdateEmail($journal, $baseUrl, $locale);
        } else {
            $notesAndComments = $this->getData('notesAndComments');
            import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');
            $emailBuilder = new PreservationEmailBuilder();
            $email = $emailBuilder->buildPreservationEmail($journal, $baseUrl, $notesAndComments, $locale);
        }
        $email->send();
    }
}
