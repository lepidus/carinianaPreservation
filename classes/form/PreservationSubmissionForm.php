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

namespace APP\plugins\generic\carinianaPreservation\classes\form;

use PKP\form\Form;
use PKP\db\DAORegistry;
use APP\template\TemplateManager;
use APP\core\Application;
use APP\plugins\generic\carinianaPreservation\classes\PreservationEmailBuilder;
use APP\plugins\generic\carinianaPreservation\classes\PreservationUpdateEmailBuilder;
use APP\plugins\generic\carinianaPreservation\classes\PreservationChangeDetector;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlBuilder;
use Illuminate\Support\Facades\Mail;
use APP\facades\Repo;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;

class PreservationSubmissionForm extends Form
{
    public CarinianaPreservationPlugin $plugin;
    public $contextId;
    private $journalDao;

    public function __construct($plugin, $contextId, $journalDao = null)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
        $this->journalDao = $journalDao ?: DAORegistry::getDAO('JournalDAO'); /** @var \JournalDAO $journalDao */
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

        $lastPreservationDate = $lastPreservationTimestamp;
        if (!empty($lastPreservationTimestamp)) {
            $ts = strtotime($lastPreservationTimestamp);
            if ($ts !== false) {
                $lastPreservationDate = date('d/m/Y H:i:s', $ts);
            }
        }

        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'emailCopies' => $emailCopies,
            'lastPreservationDate' => $lastPreservationDate,
            'isFirstPreservation' => $this->isFirstPreservation()
        ]);

        return parent::fetch($request, $template, $display);
    }

    public function isFirstPreservation(): bool
    {
        return empty($this->plugin->getSetting($this->contextId, 'lastPreservationTimestamp'));
    }

    private function getPreservationEmailCopies()
    {
        $journal = $this->journalDao->getById($this->contextId);
        if (!$journal) { return ''; }
        $contactEmail = $journal->getData('contactEmail');
        $extraCopyEmail = $this->plugin->getSetting($journal->getId(), 'extraCopyEmail');

        return (empty($extraCopyEmail) ? $contactEmail : implode(', ', [$contactEmail, $extraCopyEmail]));
    }

    public function validate($callHooks = true)
    {
        $journal = $this->journalDao->getById($this->contextId);
        $baseUrl = Application::get()->getRequest()->getBaseUrl();
        if (!$journal->getData('enableLockss')) {
            $this->addError(
                'lockssRequirement',
                __('plugins.generic.carinianaPreservation.preservationSubmission.lockssDisabled', [
                    'lockssSettingsUrl' => $this->plugin->getLockssSettingsUrl($journal, $baseUrl)
                ])
            );
        }



        $missingRequirements = $this->getMissingRequirements($journal);
         if (!empty($missingRequirements)) {
            $missingRequirementsStr = implode(', ', $missingRequirements);
            $this->addError(
                'preservationSubmission',
                __("plugins.generic.carinianaPreservation.preservationSubmission.missingRequirements",[
                    'missingRequirements' => $missingRequirementsStr
                    ])
            );
        }

        if ($this->isFirstPreservation()) {
            $statementFile = $this->plugin->getSetting($this->contextId, 'statementFile');
            if (empty($statementFile)) {
                $this->addError(
                    'statementFilePresence',
                    __("plugins.generic.carinianaPreservation.preservationSubmission.missingResponsabilityStatement")
                );
            }
        }

        if (!$this->isFirstPreservation()) {
            if (!$this->shouldSendUpdate($journal, $baseUrl)) {
                $this->addError(
                    'changesInPreservableData',
                    __('plugins.generic.carinianaPreservation.preservationSubmission.noChanges')
                );
            }
        }

        return parent::validate($callHooks);
    }

    private function getMissingRequirements($journal)
    {
        $issues = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$journal->getId()])
            ->filterByPublished(true)
            ->getMany()
            ->toArray();

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
        $journal = $this->journalDao->getById($this->contextId);
        $locale = $journal->getPrimaryLocale();
        $baseUrl = Application::get()->getRequest()->getBaseUrl();

        $isFirst = $this->isFirstPreservation();
        if (!$isFirst) {
            $emailBuilder = new PreservationUpdateEmailBuilder();
            $email = $emailBuilder->buildPreservationUpdateEmail($journal, $baseUrl, $locale);
        } else {
            $notesAndComments = $this->getData('notesAndComments');
            $emailBuilder = new PreservationEmailBuilder();
            $email = $emailBuilder->buildPreservationEmail($journal, $baseUrl, $notesAndComments, $locale);
        }
        Mail::send($email);

        if ($isFirst) {
            $this->plugin->removeStatementFile($this->contextId);
        }

        parent::execute(...$functionArgs);
    }

    protected function shouldSendUpdate($journal, $baseUrl): bool
    {
        $builder = new PreservationXmlBuilder($journal, $baseUrl);
        $journalAcronym = $journal->getLocalizedData('acronym', $journal->getPrimaryLocale());
        $tempPath = "/tmp/marcacoes_preservacao_{$journalAcronym}_check.xml";
        $builder->createPreservationXml($tempPath);
        $currentXml = is_readable($tempPath) ? file_get_contents($tempPath) : '';

        $detector = new PreservationChangeDetector($journal->getId());
        return $detector->hasChanges($currentXml);
    }
}
