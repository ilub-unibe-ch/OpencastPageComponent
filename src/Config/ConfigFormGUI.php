<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

namespace srag\Plugins\OpencastPageComponent\Config;

use ilCheckboxInputGUI;
use ilNumberInputGUI;
use ilOpencastPageComponentPlugin;
use srag\ActiveRecordConfig\OpencastPageComponent\ActiveRecordConfigFormGUI;
use srag\Plugins\OpencastPageComponent\Utils\OpencastPageComponentTrait;

/**
 * Class ConfigFormGUI
 *
 * Generated by srag\PluginGenerator v0.13.8
 *
 * @package srag\Plugins\OpencastPageComponent\Config
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author  studer + raimann ag - Team Custom 1 <info@studer-raimann.ch>
 */
class ConfigFormGUI extends ActiveRecordConfigFormGUI
{

    use OpencastPageComponentTrait;
    const PLUGIN_CLASS_NAME = ilOpencastPageComponentPlugin::class;
    const CONFIG_CLASS_NAME = Config::class;


    /**
     * @inheritdoc
     */
    protected function initFields()/*: void*/
    {
        $this->fields = [
            Config::KEY_DEFAULT_WIDTH  => [
                self::PROPERTY_CLASS    => ilNumberInputGUI::class,
                self::PROPERTY_REQUIRED => true
            ],
            Config::KEY_DEFAULT_HEIGHT => [
                self::PROPERTY_CLASS    => ilNumberInputGUI::class,
                self::PROPERTY_REQUIRED => true
            ],
            Config::KEY_DEFAULT_AS_IFRAME => [
                self::PROPERTY_CLASS    => ilCheckboxInputGUI::class,
                self::PROPERTY_REQUIRED => true
            ],
        ];
    }
}
