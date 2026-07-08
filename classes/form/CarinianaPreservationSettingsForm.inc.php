<?php

/**
 * @file CarinianaPreservationSettingsForm.inc.php
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CarinianaPreservationSettingsForm
 * @ingroup plugins_generic_carinianaPreservation
 *
 * @brief Form for site admins to modify Plaudit Pre-Endorsement plugin settings
 */


import('lib.pkp.classes.form.Form');

class CarinianaPreservationSettingsForm extends Form
{
    public const CONFIG_VARS = array(
        'extraCopyEmail' => 'string'
    );

    public $contextId;
    public $plugin;

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        $this->addCheck(new FormValidatorEmail($this, 'extraCopyEmail', 'optional'));
    }

    public function initData()
    {
        $contextId = $this->contextId;
        $plugin = &$this->plugin;
        $this->_data = array();
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $this->_data[$configVar] = $plugin->getSetting($contextId, $configVar);
        }
    }

    public function readInputData()
    {
        $userVars = array_merge(array_keys(self::CONFIG_VARS), ['temporaryFileId']);
        $this->readUserVars($userVars);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());
        $alreadyPreserved = (bool)$this->plugin->getSetting($this->contextId, 'lastPreservationTimestamp');
        $templateMgr->assign('alreadyPreserved', $alreadyPreserved);

        $journal = $request->getContext();
        $templateMgr->assign('lockssEnabled', $journal->getData('enableLockss'));
        $templateMgr->assign('lockssSettingsUrl', $this->plugin->getLockssSettingsUrl($journal, $request->getBaseUrl()));
        $statementFileData = $this->plugin->getStatementFileData($this->contextId);
        $templateMgr->assign(
            'statementFile',
            $statementFileData && $this->plugin->getStatementFilePath($this->contextId, $statementFileData)
        );

        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $plugin = &$this->plugin;
        $contextId = $this->contextId;
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $plugin->updateSetting($contextId, $configVar, trim((string)$this->getData($configVar)), $type);
        }

        $temporaryFileId = $this->getData('temporaryFileId');
        $alreadyPreserved = (bool)$plugin->getSetting($contextId, 'lastPreservationTimestamp');
        if ($temporaryFileId && !$alreadyPreserved) {
            $this->saveResponsabilityStatementFile($contextId, $plugin, $temporaryFileId);
        }

        parent::execute(...$functionArgs);
    }

    private function saveResponsabilityStatementFile($contextId, $plugin, $temporaryFileId)
    {
        $user = Application::get()->getRequest()->getUser();
        $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /** @var TemporaryFileDAO $temporaryFileDao */
        $statementTempFile = $temporaryFileDao->getTemporaryFile(
            $temporaryFileId,
            $user->getId()
        );

        if (!$statementTempFile || !$plugin->isAllowedStatementFile($statementTempFile->getOriginalFileName(), $statementTempFile->getFileType())) {
            return;
        }

        $statementFileName = $this->moveStatementTempFile($contextId, $plugin, $statementTempFile, $user->getId());

        if ($statementFileName) {
            $statementFileData = json_encode([
                'originalFileName' => $statementTempFile->getOriginalFileName(),
                'fileName' => $statementFileName,
                'fileType' => $statementTempFile->getFileType(),
            ]);

            $plugin->updateSetting($contextId, 'statementFile', $statementFileData);
        }
    }

    private function moveStatementTempFile($contextId, $plugin, $statementTempFile, $userId)
    {
        import('lib.pkp.classes.file.TemporaryFileManager');
        import('lib.pkp.classes.file.PrivateFileManager');
        $temporaryFileManager = new TemporaryFileManager();
        $privateFileManager = new PrivateFileManager();
        $dir = $plugin->getStatementFileDirectory((int)$contextId);
        if (!$privateFileManager->fileExists($dir, 'dir')) {
            $privateFileManager->mkdirtree($dir);
        }
        $statementFileName = $plugin->getStatementFileNameForOriginal($statementTempFile->getOriginalFileName());
        if (!$statementFileName) {
            return false;
        }
        $targetPath = $dir . '/' . $statementFileName;
        copy($statementTempFile->getFilePath(), $targetPath);
        if (is_file($targetPath)) {
            $privateFileManager->setMode($targetPath, FILE_MODE_MASK);
            $temporaryFileManager->deleteById($statementTempFile->getId(), $userId);
            return $statementFileName;
        }
        return false;
    }
}
