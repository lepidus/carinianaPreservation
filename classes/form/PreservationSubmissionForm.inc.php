<?php

/**
 * @file PreservationSubmissionForm.inc.php
 *
 * Copyright (c) 2023 Lepidus Tecnologia
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

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $emailCopies = $this->getPreservationEmailCopies();

        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'applicationName' => Application::get()->getName(),
            'emailCopies' => $emailCopies
        ]);

        return parent::fetch($request, $template, $display);
    }

    private function getPreservationEmailCopies(): string
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

        list($requirementsAreMissing, $missingRequirements) = $this->requirementsAreMissing($journal);
        if($requirementsAreMissing) {
            $missingRequirements = implode(', ', $missingRequirements);
            $this->addError('preservationSubmission', __("plugins.generic.carinianaPreservation.preservationSubmission.missingRequirements", ['missingRequirements' => $missingRequirements]));
        }

        $statementFile = $this->plugin->getSetting($this->contextId, 'statementFile');
        if(empty($statementFile)) {
            $this->addError('preservationSubmission', __("plugins.generic.carinianaPreservation.preservationSubmission.missingResponsabilityStatement"));
        }

        return parent::validate($callHooks);
    }

    private function requirementsAreMissing($journal): array
    {
        $requirements = [
            'manager.setup.publisher' => $journal->getData('publisherInstitution'),
            'manager.setup.contextTitle' => $journal->getLocalizedData('name'),
            'manager.setup.printIssn' => $journal->getData('printIssn'),
            'manager.setup.onlineIssn' => $journal->getData('onlineIssn'),
            'context.path' => $journal->getData('urlPath'),
            'manager.setup.contextSummary' => $journal->getLocalizedData('description'),
            'manager.setup.contextInitials' => $journal->getLocalizedData('acronym'),
            'admin.settings.contactEmail' => $journal->getData('contactEmail')
        ];

        $requirementsAreMissing = false;
        $missingRequirements = [];
        foreach($requirements as $name => $value) {
            if(empty($value)) {
                $requirementsAreMissing = true;
                $missingRequirements[] = __($name);
            }
        }

        return [$requirementsAreMissing, $missingRequirements];
    }

    public function execute(...$functionArgs)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');

        $locale = AppLocale::getLocale();
        $journal = $journalDao->getById($this->contextId);
        $baseUrl = Application::get()->getRequest()->getBaseUrl();
        $preservationName = __('plugins.generic.carinianaPreservation.displayName');
        $preservationEmail = $this->plugin->getSetting($this->contextId, 'recipientEmail');

        import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');
        $preservationEmailBuilder = new PreservationEmailBuilder();
        $email = $preservationEmailBuilder->buildPreservationEmail($journal, $baseUrl, $locale);
        $email->send();
    }
}
