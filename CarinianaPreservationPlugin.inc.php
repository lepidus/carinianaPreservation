<?php

/**
 * @file plugins/generic/carinianaPreservation/CarinianaPreservationPlugin.inc.php
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt.
 *
 * @class CarinianaPreservationPlugin
 * @ingroup plugins_generic_carinianaPreservation
 * @brief Cariniana Preservation Plugin
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.file.FileManager');

class CarinianaPreservationPlugin extends GenericPlugin
{
    private const STATEMENT_ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    private const STATEMENT_ALLOWED_MIME_TYPES_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'addTasksToCronTab'));

        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }

        if (!$this->getSetting(0, 'legacyStatementMigrationDone')) {
            $this->import('classes.migration.LegacyStatementMigration');
            (new LegacyStatementMigration($this))->run();
            $this->updateSetting(0, 'legacyStatementMigrationDone', 1, 'bool');
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.carinianaPreservation.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.carinianaPreservation.description');
    }

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        return array_merge(
            array(
                new LinkAction(
                    'preservationSubmission',
                    new AjaxModal($router->url($request, null, null, 'manage', null, array('verb' => 'preservationSubmission', 'plugin' => $this->getName(), 'category' => 'generic')), __('plugins.generic.carinianaPreservation.preservationSubmission')),
                    __('plugins.generic.carinianaPreservation.preservationSubmission'),
                ),
                new LinkAction(
                    'settings',
                    new AjaxModal($router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')), $this->getDisplayName()),
                    __('manager.plugins.settings'),
                )
            ),
            parent::getActions($request, $actionArgs)
        );
    }

    public function manage($args, $request)
    {
        $context = $request->getContext();
        $contextId = ($context == null) ? 0 : $context->getId();

        switch ($request->getUserVar('verb')) {
            case 'settings':
                return $this->handlePluginForm($request, $contextId, 'CarinianaPreservationSettingsForm');
            case 'preservationSubmission':
                return $this->handlePluginForm($request, $contextId, 'PreservationSubmissionForm');
            case 'downloadStatement':
                $fileManager = new FileManager();
                $filePath = $this->getPluginPath() . '/resources/Termo_de_Responsabilidade.doc';
                $fileManager->downloadByPath(
                    $filePath,
                    'application/msword',
                    false,
                    basename($filePath)
                );
                return null;
            case 'uploadStatementFile':
                return $this->saveStatementFile($request);
        }
        return parent::manage($args, $request);
    }

    public function handlePluginForm($request, $contextId, $formClass)
    {
        $this->import('classes.form.'.$formClass);
        $form = new $formClass($this, $contextId);
        if ($request->getUserVar('save')) {
            if (!$request->isPost() || !$request->checkCSRF()) {
                return new JSONMessage(false, __('form.csrfInvalid'));
            }
            $form->readInputData();
            if ($form->validate()) {
                $form->execute();
                return new JSONMessage(true);
            }
        } else {
            $form->initData();
        }
        return new JSONMessage(true, $form->fetch($request));
    }

    public function saveStatementFile($request)
    {
        $user = $request->getUser();
        if (!$user || !$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        import('lib.pkp.classes.file.TemporaryFileManager');
        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
        if ($temporaryFile) {
            if (!$this->isAllowedStatementFile($temporaryFile->getOriginalFileName(), $temporaryFile->getFileType())) {
                $temporaryFileManager->deleteById($temporaryFile->getId(), $user->getId());
                return new JSONMessage(false, __('common.uploadFailed'));
            }
            $json = new JSONMessage(true);
            $json->setAdditionalAttributes(array(
                'temporaryFileId' => $temporaryFile->getId()
            ));
            return $json;
        } else {
            return new JSONMessage(false, __('common.uploadFailed'));
        }
    }

    public function addTasksToCronTab($hookName, $args)
    {
        $taskFilesPath = &$args[0];
        $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
        return false;
    }

    public function removeStatementFile(int $journalId): void
    {
        $statementData = $this->getStatementFileData($journalId);
        if (!$statementData) {
            $this->updateSetting($journalId, 'statementFile', null);
            return;
        }
        $path = $this->getStatementFilePath($journalId, $statementData);
        if (!$path) {
            $this->updateSetting($journalId, 'statementFile', null);
            return;
        }
        if (is_file($path)) {
            @unlink($path);
        }
        $this->updateSetting($journalId, 'statementFile', null);
    }

    public function getLockssSettingsUrl($journal, $baseUrl)
    {
        return $baseUrl . '/index.php/' . $journal->getPath() . '/management/settings/distribution#archive/lockss';
    }

    public function getStatementFileData(int $journalId): ?array
    {
        $statementDataJson = $this->getSetting($journalId, 'statementFile');
        if (!$statementDataJson) {
            return null;
        }

        $statementData = json_decode($statementDataJson, true);
        if (!is_array($statementData)) {
            return null;
        }

        foreach (['originalFileName', 'fileName', 'fileType'] as $field) {
            if (empty($statementData[$field]) || !is_string($statementData[$field])) {
                return null;
            }
        }

        $fileName = $statementData['fileName'];
        if (basename($fileName) !== $fileName) {
            return null;
        }

        if (!$this->isAllowedStatementFile($fileName, $statementData['fileType'])) {
            return null;
        }

        $statementData['originalFileName'] = basename($statementData['originalFileName']);
        return $statementData;
    }

    public function getStatementFilePath(int $journalId, array $statementData): ?string
    {
        if (empty($statementData['fileName']) || basename($statementData['fileName']) !== $statementData['fileName']) {
            return null;
        }

        $directory = $this->getStatementFileDirectory($journalId);
        $realDirectory = realpath($directory);
        if (!$realDirectory) {
            return null;
        }

        $realPath = realpath($directory . '/' . $statementData['fileName']);
        if (!$realPath || strpos($realPath, $realDirectory . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $realPath;
    }

    public function getStatementFileDirectory(int $journalId): string
    {
        import('lib.pkp.classes.file.PrivateFileManager');
        $privateFileManager = new PrivateFileManager();
        $base = rtrim($privateFileManager->getBasePath(), '/');
        return $base . '/carinianaPreservation/' . (int)$journalId;
    }

    public function getStatementFileNameForOriginal(string $originalFileName): ?string
    {
        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::STATEMENT_ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return 'responsabilityStatement.' . $extension;
    }

    public function isAllowedStatementFile(string $fileName, ?string $fileType): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::STATEMENT_ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        return $fileType && in_array($fileType, self::STATEMENT_ALLOWED_MIME_TYPES_BY_EXTENSION[$extension], true);
    }
}
