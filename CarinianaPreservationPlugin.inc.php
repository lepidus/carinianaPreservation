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
                $this->import('classes.form.CarinianaPreservationSettingsForm');
                $form = new CarinianaPreservationSettingsForm($this, $contextId);
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
            case 'preservationSubmission':
                $this->import('classes.form.PreservationSubmissionForm');
                $form = new PreservationSubmissionForm($this, $contextId);
                if ($request->getUserVar('save')) {
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }
}
