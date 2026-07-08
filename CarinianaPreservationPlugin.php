<?php

/**
 * @file plugins/generic/carinianaPreservation/CarinianaPreservationPlugin.inc.php
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt.
 *
 * @class CarinianaPreservationPlugin
 *
 * @ingroup plugins_generic_carinianaPreservation
 *
 * @brief Cariniana Preservation Plugin
 */

namespace APP\plugins\generic\carinianaPreservation;

use APP\core\Application;
use APP\plugins\generic\carinianaPreservation\classes\form\CarinianaPreservationSettingsForm;
use APP\plugins\generic\carinianaPreservation\classes\form\PreservationSubmissionForm;
use PKP\core\JSONMessage;
use PKP\file\FileManager;
use PKP\file\PrivateFileManager;
use PKP\file\TemporaryFileManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

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

        Hook::add('AcronPlugin::parseCronTab', [$this, 'addTasksToCronTab']);

        if (Application::isUnderMaintenance()) {
            return true;
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
            [
                new LinkAction(
                    'preservationSubmission',
                    new AjaxModal($router->url($request, null, null, 'manage', null, ['verb' => 'preservationSubmission', 'plugin' => $this->getName(), 'category' => 'generic']), __('plugins.generic.carinianaPreservation.preservationSubmission')),
                    __('plugins.generic.carinianaPreservation.preservationSubmission'),
                ),
                new LinkAction(
                    'settings',
                    new AjaxModal($router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']), $this->getDisplayName()),
                    __('manager.plugins.settings'),
                )
            ],
            parent::getActions($request, $actionArgs)
        );
    }

    public function manage($args, $request)
    {
        $context = $request->getContext();
        $contextId = ($context == null) ? 0 : $context->getId();

        switch ($request->getUserVar('verb')) {
            case 'settings':
                $form = new CarinianaPreservationSettingsForm($this, $contextId);
                return $this->handlePluginForm($request, $form);
            case 'preservationSubmission':
                $form = new PreservationSubmissionForm($this, $contextId);
                return $this->handlePluginForm($request, $form);
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

    public function handlePluginForm($request, $form)
    {
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

        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
        if ($temporaryFile) {
            if (!$this->isAllowedStatementFile($temporaryFile->getOriginalFileName(), $temporaryFile->getFileType())) {
                $temporaryFileManager->deleteById($temporaryFile->getId(), $user->getId());
                return new JSONMessage(false, __('common.uploadFailed'));
            }
            $json = new JSONMessage(true);
            $json->setAdditionalAttributes([
                'temporaryFileId' => $temporaryFile->getId()
            ]);
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
