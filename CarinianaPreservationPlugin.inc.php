<?php

/**
 * @file plugins/generic/carinianaPreservation/CarinianaPreservationPlugin.inc.php
 *
 * Copyright (c) 2023 Lepidus Tecnologia
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
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
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
                $fileManager->downloadByPath($filePath);
                // no break
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

        import('lib.pkp.classes.file.TemporaryFileManager');
        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
        if ($temporaryFile) {
            $json = new JSONMessage(true);
            $json->setAdditionalAttributes(array(
                'temporaryFileId' => $temporaryFile->getId()
            ));
            return $json;
        } else {
            return new JSONMessage(false, __('common.uploadFailed'));
        }
    }
}
