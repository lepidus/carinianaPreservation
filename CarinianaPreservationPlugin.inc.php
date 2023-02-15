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

        // if ($success && $this->getEnabled($mainContextId)) {
        // }

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
}
