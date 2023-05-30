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
import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');

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
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());
        return parent::fetch($request, $template, $display);
    }

    public function validate($callHooks = true)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journal = $journalDao->getById($this->contextId);

        $locale = AppLocale::getLocale();
        $necessaryData = [
            $journal->getData('publisherInstitution'),
            $journal->getLocalizedData('name', $locale),
            $journal->getData('printIssn'),
            $journal->getData('onlineIssn'),
            $journal->getData('urlPath'),
            $journal->getLocalizedData('description', $locale),
            $journal->getLocalizedData('acronym', $locale),
            $journal->getData('contactEmail')
        ];

        foreach($necessaryData as $data) {
            if(empty($data))
                $this->addError('preservationSubmission', __("plugins.generic.carinianaPreservation.preservationSubmission.missingData"));
        }
        
        $statementFile = $this->plugin->getSetting($this->contextId, 'statementFile');
        if(empty($data))
            $this->addError('preservationSubmission', __("plugins.generic.carinianaPreservation.preservationSubmission.missingResponsabilityStatement"));

        return parent::validate($callHooks);
    }
    
    public function execute(...$functionArgs)
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        
        $locale = AppLocale::getLocale();
        $journal = $journalDao->getById($this->contextId);
        $baseUrl = Application::get()->getRequest()->getBaseUrl();
        $preservationName = __('plugins.generic.carinianaPreservation.displayName');
        $preservationEmail = $this->plugin->getSetting($this->contextId, 'recipientEmail');

        $preservationEmailBuilder = new PreservationEmailBuilder();
        $email = $preservationEmailBuilder->buildPreservationEmail($journal, $baseUrl, $preservationName, $preservationEmail, $locale);
        $email->send();
    }
}
