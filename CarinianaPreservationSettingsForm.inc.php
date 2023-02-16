<?php

/**
 * @file CarinianaPreservationSettingsForm.inc.php
 *
 * Copyright (c) 2023 Lepidus Tecnologia
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
        'recipientEmail' => 'string',
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
        $this->addCheck(new FormValidator($this, 'recipientEmail', 'required', 'plugins.generic.carinianaPreservation.settings.recipientEmail'));
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
        $this->readUserVars(array_keys(self::CONFIG_VARS));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $plugin = &$this->plugin;
        $contextId = $this->contextId;
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $plugin->updateSetting($contextId, $configVar, $this->getData($configVar), $type);
        }

        parent::execute(...$functionArgs);
    }
}
