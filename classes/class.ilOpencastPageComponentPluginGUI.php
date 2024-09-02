<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\UI\Component\Input\Container\Form\Form;
use srag\CustomInputGUIs\OpencastPageComponent\TableGUI\TableGUI;
use srag\DIC\OpencastPageComponent\DICTrait;
use srag\DIC\OpencastPageComponent\Exception\DICException;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\TermsOfUse\ToUManager;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\OpencastPageComponent\Authorization\TokenRepository;
use srag\Plugins\OpencastPageComponent\Config\Config;
use srag\Plugins\OpencastPageComponent\Utils\OpencastPageComponentTrait;

/**
 * Class ilOpencastPageComponentPluginGUI
 *
 * Generated by srag\PluginGenerator v0.13.8
 *
 * @author            studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 * @author            studer + raimann ag - Team Custom 1 <info@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy ilOpencastPageComponentPluginGUI: ilPCPluggedGUI
 */
class ilOpencastPageComponentPluginGUI extends ilPageComponentPluginGUI
{
    public const CMD_CANCEL = "cancel";
    public const CMD_CREATE = "create";
    public const CMD_CREATE_PLUG = "create_plug";
    public const CMD_EDIT = "edit";
    public const CMD_INSERT = "insert";
    public const CMD_UPDATE = "update";
    public const CMD_APPLY_FILTER = "applyFilter";
    public const CMD_RESET_FILTER = "resetFilter";
    public const CMD_SHOW_UPLOAD_FORM = 'showUploadForm';
    public const CMD_UPLOAD = 'upload';
    public const CUSTOM_CMD = 'ocpc_cmd';
    public const PROP_EVENT_ID = 'event_id';
    public const PROP_WIDTH = 'width';
    public const PROP_HEIGHT = 'height';
    public const PROP_AS_LINK = 'as_link';
    public const POST_SIZE = 'size';
    public const MODE_EDIT = 'edit';
    public const MODE_PRESENTATION = 'presentation';
    public const MODE_PREVIEW = 'preview';
    public const PROP_POSITION = 'position';
    public const POSITION_LEFT = 'left';
    public const POSITION_CENTER = 'center';
    public const POSITION_RIGHT = 'right';
    public const PROP_RESPONSIVE = 'responsive';

    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    public $player_url;

    /**
     * @var Container
     */
    protected $dic;
    /**
     * @var \srag\Plugins\Opencast\DI\OpencastDIC
     */
    protected $opencast_dic;
    /**
     * @var EventAPIRepository
     */
    protected $event_repository;
    /**
     * @var \ilOpenCastPlugin
     */
    protected $opencast_plugin;

    /**
     * ilOpencastPageComponentPluginGUI constructor
     */
    public function __construct()
    {
        global $DIC, $opencastContainer;
        $this->dic = $DIC;
        $this->main_tpl = $this->dic->ui()->mainTemplate();
        $this->opencast_plugin = ilOpenCastPlugin::getInstance();
        $main_opencast_js_path = $this->opencast_plugin->getDirectory() . '/js/opencast/dist/index.js';
        PluginConfig::setApiSettings();
        if (file_exists($main_opencast_js_path)) {
            $this->dic->ui()->mainTemplate()->addJavaScript($main_opencast_js_path);
        }
        $this->opencast_dic = OpencastDIC::getInstance();
        $this->opencast_dic->overwriteService(
            'upload_handler',
            new xoctFileUploadHandlerGUI(
                $this->opencast_dic->upload_storage_service(),
                $this->dic->ctrl()->getLinkTargetByClass(
                    [ilObjPluginDispatchGUI::class, ocpcRouterGUI::class, xoctFileUploadHandlerGUI::class], 'upload'
                ),
                $this->dic->ctrl()->getLinkTargetByClass(
                    [ilObjPluginDispatchGUI::class, ocpcRouterGUI::class, xoctFileUploadHandlerGUI::class], 'info'
                ),
                $this->dic->ctrl()->getLinkTargetByClass(
                    [ilObjPluginDispatchGUI::class, ocpcRouterGUI::class, xoctFileUploadHandlerGUI::class], 'remove'
                )
            )
        );

        if (method_exists($this->opencast_dic, 'event_repository')) {
            $this->event_repository = $this->opencast_dic->event_repository();
        } elseif ($opencastContainer && isset($opencastContainer[EventAPIRepository::class])) {
            $this->event_repository = $opencastContainer[EventAPIRepository::class];
        }

        parent::__construct();
    }

    public function executeCommand(): void
    {
        try {
            $this->dic->ctrl()->getNextClass();
            $cmd = $this->dic->ctrl()->getCmd();
            $custom_command = $this->dic->http()->request()->getQueryParams()[self::CUSTOM_CMD] ?? null;

            if ($cmd === self::CMD_INSERT && $custom_command !== null) {
                $cmd = $custom_command;
                $this->performCommand($cmd);
                return;
            } else {
                $cmd = $this->dic->ctrl()->getCmd();
                $this->performCommand($cmd);
                return;
            }
        } catch (ilException $e) {
            $this->main_tpl->setOnScreenMessage('failure', $e->getMessage(), true);
            $this->dic->ctrl()->returnToParent($this);
        }
    }

    public function performCommand(string $cmd): void
    {
        switch ($cmd) {
            case self::CMD_CANCEL:
            case self::CMD_CREATE:
            case self::CMD_INSERT:
            case self::CMD_EDIT:
            case self::CMD_UPDATE:
            case self::CMD_APPLY_FILTER:
            case self::CMD_RESET_FILTER:
            case self::CMD_SHOW_UPLOAD_FORM:
            case self::CMD_UPLOAD:
                $this->{$cmd}();
                break;
            default:
                break;
        }
    }

    protected function addToolbar(): void
    {
        $upload_button = ilLinkButton::getInstance();
        $upload_button->setPrimary(true);
//        $this->dic->ctrl()->saveParameter($this, 'rtoken'); // TODO why???
        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_SHOW_UPLOAD_FORM);
        $upload_button->setUrl($this->dic->ctrl()->getLinkTarget($this, self::CMD_INSERT));
        $upload_button->setCaption($this->plugin->txt('btn_upload'), false);
        $this->dic->toolbar()->addButtonInstance($upload_button);
    }

    protected function showUploadForm(): void
    {
        $form = $this->getUploadForm();
        $this->main_tpl->setContent(
            $this->dic->ui()->renderer()->render($form)
        );
    }

    protected function upload(): void
    {
    }

    protected function getUploadForm(): Form
    {
        $return_link = $this->dic->ctrl()->getLinkTarget($this, 'insert_plug_OpencastPageComponent');
        $this->dic->ctrl()->setParameterByClass(
            ocpcRouterGUI::class, ocpcRouterGUI::P_GET_RETURN_LINK, urlencode($return_link)
        );

        $with_terms_of_use = !ToUManager::hasAcceptedToU($this->dic->user()->getId());

        $form_action_by_class = $this->dic->ctrl()->getFormActionByClass(
            [ilObjPluginDispatchGUI::class, ocpcRouterGUI::class],
            self::CMD_CREATE
        );

        return $this->opencast_dic->event_form_builder()->upload(
            $form_action_by_class,
            $with_terms_of_use
        );
    }

    protected function getTable(bool $init_data = true): VideoSearchTableGUI
    {
        $this->dic->ctrl()->clearParameterByClass(self::class, self::CUSTOM_CMD);
        $command_url = $this->dic->ctrl()->getLinkTarget($this, self::CMD_CREATE);
        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_APPLY_FILTER);
        $table = new VideoSearchTableGUI($this, self::CMD_INSERT, $this->dic, $command_url);
        $table->setFilterCommand(self::CMD_INSERT);
        if ($init_data) {
            $table->initializeData();
        }

        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, self::CMD_RESET_FILTER);
        $reset_filter_url = $this->dic->ctrl()->getLinkTarget($this, self::CMD_INSERT);
        $reset_filter = $this->lng->txt('reset_filter');

        $this->dic->ui()->mainTemplate()->addOnLoadCode(
            'OpencastPageComponent.overwriteResetButton("' . $reset_filter . '", "' . $reset_filter_url . '");'
        );

        $this->dic->ctrl()->clearParameters($this);

        return $table;
    }

    protected function getForm(): ilPropertyFormGUI
    {
        $this->dic->ui()->mainTemplate()->addJavaScript(
            $this->getPlugin()->getDirectory() . '/node_modules/ion-rangeslider/js/ion.rangeSlider.min.js'
        );
        $this->dic->ui()->mainTemplate()->addCss(
            $this->getPlugin()->getDirectory() . '/node_modules/ion-rangeslider/css/ion.rangeSlider.min.css'
        );
        $this->dic->ui()->mainTemplate()->addCss($this->getPlugin()->getDirectory() . '/templates/css/form.css');
        $this->dic->ui()->mainTemplate()->addJavaScript(
            $this->getPlugin()->getDirectory() . '/templates/js/form.min.js?v=3'
        );
        $this->dic->ui()->mainTemplate()->addOnLoadCode(
            'OpencastPageComponent.initForm(' .
            Config::getField(Config::KEY_DEFAULT_WIDTH) * 2 .
            ');'
        );

        $form = new ilPropertyFormGUI();
        $form->setId('ocpc_edit');
        $prop = $this->getProperties();
        $event = $this->event_repository->find($prop[self::PROP_EVENT_ID]);

        // thumbnail
        $max_width = 1000;
        $ratio = round((int) $prop['width'] / (int) $prop['height'], 1);
        if ((int) $prop['width'] > $max_width) {
            $max_width = (int) $prop['width'];
        }
        $max_height = round($max_width / $ratio, 1);
        $thumbnail = new ilNonEditableValueGUI($this->dic->language()->txt('preview'), '', true);
        $container = '<div id="ocpc_thumbnail_container" style="width:100%%; height:100%%; overflow:auto;">%s</div>';
        $wrapper = '<div id="ocpc_thumbnail_wrapper" style="width:' . $max_width . 'px; height:' . $max_height . 'px;">%s</div>';
        $img = '<img width="' . $prop['width'] . '" height="' . $prop['height'] .
            '" id="ocpc_thumbnail" src="' . $event->publications()->getThumbnailUrl() . '">';
        $thumbnail_value = sprintf($container, sprintf($wrapper, $img));
        $thumbnail->setValue($thumbnail_value);
        $form->addItem($thumbnail);

        // width height
        $width_height = new ilWidthHeightInputGUI($this->plugin->txt("height_width"), self::POST_SIZE);
        $width_height->setConstrainProportions(true);
        $width_height->setRequired(true);
        $width_height->setValueByArray([self::POST_SIZE => array_merge($prop, ['constr_prop' => true])]);
        $form->addItem($width_height);

        // slider
        $slider = new ilNonEditableValueGUI('', '', true);
        $slider_tpl = $this->getPlugin()->getTemplate('html/slider_input.html', false, false);
        $slider_tpl->setVariable('CONFIG', json_encode($this->getRangeSliderConfig()));
        $slider->setValue($slider_tpl->get());
        $form->addItem($slider);

        // positioning
        $positioning = new ilSelectInputGUI($this->dic->language()->txt("position"), self::PROP_POSITION);
        $positioning->setOptions([
            self::POSITION_LEFT => $this->dic->language()->txt('pos_' . self::POSITION_LEFT),
            self::POSITION_CENTER => $this->dic->language()->txt('cont_' . self::POSITION_CENTER),
            self::POSITION_RIGHT => $this->dic->language()->txt('pos_' . self::POSITION_RIGHT),
        ]);
        $positioning->setRequired(true);
        $positioning->setValue($prop[self::PROP_POSITION] ?? self::POSITION_LEFT);
        $form->addItem($positioning);

        // responsiveness
        $resp = new ilCheckboxInputGUI($this->plugin->txt("responsiveness"), self::PROP_RESPONSIVE);
        $resp->setInfo($this->plugin->txt("responsiveness_info"));
        $resp->setChecked($prop[self::PROP_RESPONSIVE] ?? true);
        $form->addItem($resp);

        // as iframe
        $as_iframe = new ilCheckboxInputGUI($this->getPlugin()->txt(self::PROP_AS_LINK), self::PROP_AS_LINK);
        $as_iframe->setInfo($this->getPlugin()->txt(self::PROP_AS_LINK . '_info'));
        $as_iframe->setChecked($prop[self::PROP_AS_LINK]);
        $form->addItem($as_iframe);

        $form->addCommandButton(self::CMD_UPDATE, $this->dic->language()->txt("save"));
        $form->addCommandButton(self::CMD_CANCEL, $this->dic->language()->txt("cancel"));
        $form->setTitle($this->getPlugin()->txt("form_title"));

        $form->setFormAction($this->dic->ctrl()->getFormAction($this));

        return $form;
    }

    public function insert(): void
    {
        $this->addToolbar();
        $table = $this->getTable();
        $this->main_tpl->setContent($table->getHTML());
    }

    protected function applyFilter(): void
    {
        $table = $this->getTable(false);
        $table->setFilterCommand(self::CMD_INSERT);
        $table->resetOffset();
        $table->storeProperty('offset', 0);
        $table->writeFilterToSession();
        $this->redirect(self::CMD_INSERT);
    }

    public function resetFilter(): void
    {
        $table = $this->getTable();
        $table->resetOffset();
        $table->storeProperty('offset', 0);
        $table->resetFilter();
        $this->redirect(self::CMD_INSERT);
    }

    public function create(): void
    {
        $event_id = filter_input(INPUT_GET, VideoSearchTableGUI::GET_PARAM_EVENT_ID, FILTER_SANITIZE_STRING);
        $properties = [
            self::PROP_EVENT_ID => $event_id,
            self::PROP_HEIGHT => Config::getField(Config::KEY_DEFAULT_HEIGHT),
            self::PROP_WIDTH => Config::getField(Config::KEY_DEFAULT_WIDTH),
            self::PROP_POSITION => self::POSITION_LEFT,
            self::PROP_RESPONSIVE => true,
            self::PROP_AS_LINK => (bool) Config::getField(Config::KEY_DEFAULT_AS_LINK)
        ];
        $this->createElement($properties);
        $this->main_tpl->setOnScreenMessage('success', $this->plugin->txt('msg_added'), true);

        $pc_id = $this->getPCGUI()->getContentObject()->readPCId();
        $this->dic->ctrl()->setParameter($this, 'pc_id', $pc_id);
        $this->dic->ctrl()->setParameter($this, 'hier_id', 1);
        $this->dic->ctrl()->redirect($this, self::CMD_EDIT);
    }

    public function edit(): void
    {
        $this->main_tpl->setContent($this->getForm()->getHTML());
    }

    public function update(): void
    {
        $form = $this->getForm();

        $form->setValuesByPost();

        if (!$form->checkInput()) {
            $this->main_tpl->setContent($form->getHTML());

            return;
        }

        $properties = $this->getProperties();

        $size = $form->getInput(self::POST_SIZE);
        $properties[self::PROP_HEIGHT] = $size[self::PROP_HEIGHT];
        $properties[self::PROP_WIDTH] = $size[self::PROP_WIDTH];
        $properties[self::PROP_POSITION] = $form->getInput(self::PROP_POSITION);
        $properties[self::PROP_RESPONSIVE] = $form->getInput(self::PROP_RESPONSIVE);
        $properties[self::PROP_AS_LINK] = $form->getInput(self::PROP_AS_LINK);

        $this->updateElement($properties);

        $this->returnToParent();
    }

    public function cancel(): void
    {
        $this->returnToParent();
    }

    /**
     * @param string $a_mode
     * @param string $plugin_version
     *
     * @throws ilTemplateException
     * @throws xoctException
     */
    public function getElementHTML($a_mode, array $a_properties, $plugin_version): string
    {
        try {
            $event = $this->event_repository->find($a_properties[self::PROP_EVENT_ID]);
        } catch (Exception $e) {
            return $this->getExceptionHTML($a_properties);
        }
        $as_link = (bool) $a_properties[self::PROP_AS_LINK];
        if (!$as_link && ($a_mode == self::MODE_PRESENTATION)) {
            return $this->getIframeHTML($a_properties, $event);
        } else {
            return $this->getStandardElementHTML($a_mode, $a_properties, $event);
        }
    }

    public function redirect(string $cmd): void
    {
        $this->dic->ctrl()->setParameter($this, self::CUSTOM_CMD, $cmd);
        $this->dic->ctrl()->redirect($this, self::CMD_INSERT);
    }

    /**
     *
     *
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function getIframeHTML(array $properties, Event $event): string
    {
        $tpl = $this->getPlugin()->getTemplate('html/component_as_iframe.html');
        $this->dic->ui()->mainTemplate()->addCss(
            $this->getPlugin()->getDirectory() . '/templates/css/presentation.css'
        );
        $tpl->setVariable('SRC', $this->getPlayerLink($event));
        $this->setStyleFromProps($tpl, $properties);

        return $tpl->get();
    }

    /**
     *
     *
     * @throws ilTemplateException
     * @throws xoctException
     */
    protected function getStandardElementHTML(string $mode, array $properties, Event $event): string
    {
        $renderer = new xoctEventRenderer($event);
        $use_modal = (PluginConfig::getConfig(PluginConfig::F_USE_MODALS));
        $tpl = $this->getPlugin()->getTemplate('html/component_as_link.html');
        $this->setStyleFromProps($tpl, $properties);
        $tpl->setVariable('THUMBNAIL_URL', $event->publications()->getThumbnailUrl());
        $this->dic->ui()->mainTemplate()->addCss(
            $this->getPlugin()->getDirectory() . '/templates/css/presentation.css'
        );
        if ($mode == self::MODE_PRESENTATION || $mode == self::MODE_PREVIEW) {
            $tpl->setVariable('TARGET', '_blank');
            $tpl->setVariable('VIDEO_LINK', $use_modal ? '#' : $this->getPlayerLink($event));
            $tpl->touchBlock('overlay');
            if ($use_modal) {
                $tpl->setVariable('MODAL', $renderer->getPlayerModal()->getHTML());
                $tpl->setVariable('MODAL_LINK', $renderer->getModalLink());
            }
        } else {
            $tpl->setVariable('VIDEO_LINK', '#');
        }

        return $tpl->get();
    }

    protected function getExceptionHTML(array $properties): string
    {
        return '<img src="Services/WebAccessChecker/templates/images/access_denied.png" ' .
            'height="' . $properties[self::PROP_HEIGHT] . 'px" ' .
            'width="' . $properties[self::PROP_WIDTH] . 'px">';
    }

    /**
     * @return array{skin: string, min: int, max: int, from: int, from_min: int, step: int, grid: true, postfix: string}
     */
    protected function getRangeSliderConfig(): array
    {
        return [
            'skin' => 'modern',
            'min' => 0,
            'max' => 100,
            'from' => 50,
            'from_min' => 10,
            'step' => 1,
            'grid' => true,
            'postfix' => '%',
        ];
    }

    /**
     * @throws xoctException
     */
    protected function getPlayerLink(Event $event): string
    {
        if (PluginConfig::getConfig(PluginConfig::F_INTERNAL_VIDEO_PLAYER) || $event->isLiveEvent()) {
            $token = (new TokenRepository())->create($this->dic->user()->getId(), $event->getIdentifier());
            $this->dic->ctrl()->clearParametersByClass(xoctPlayerGUI::class);
            $this->dic->ctrl()->setParameterByClass(
                ocpcRouterGUI::class, ocpcRouterGUI::TOKEN, $token->getToken()->toString()
            );
            $this->dic->ctrl()->setParameterByClass(
                ocpcRouterGUI::class, xoctPlayerGUI::IDENTIFIER, $event->getIdentifier()
            );
            $this->dic->ctrl()->setParameterByClass(
                xoctPlayerGUI::class, xoctPlayerGUI::IDENTIFIER, $event->getIdentifier()
            );
            return $this->dic->ctrl()->getLinkTargetByClass(
                [ilObjPluginDispatchGUI::class, ocpcRouterGUI::class, xoctPlayerGUI::class],
                xoctPlayerGUI::CMD_STREAM_VIDEO
            );
        }
        if (!(property_exists($this, 'player_url') && $this->player_url !== null)) {
            $url = $event->publications()->getFirstPublicationMetadataForUsage(
                PublicationUsage::find(PublicationUsage::USAGE_PLAYER)
            )->getUrl();
            if (PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS)) {
                $this->player_url = xoctSecureLink::signPlayer($url);
            } else {
                $this->player_url = $url;
            }
        }

        return $this->player_url;
    }

    protected function setStyleFromProps(ilTemplate $tpl, array $properties)
    {
        $ratio = $properties[self::PROP_WIDTH] ? ($properties[self::PROP_HEIGHT] / ($properties[self::PROP_WIDTH])) * 100 : 1;
        $tpl->setVariable('RATIO', $ratio);
        $tpl->setVariable('MAX-WIDTH', $properties[self::PROP_WIDTH]);
        $tpl->setVariable('MAX-HEIGHT', $properties[self::PROP_HEIGHT]);
        if (array_key_exists(self::PROP_RESPONSIVE, $properties)) {
            if ($properties[self::PROP_RESPONSIVE] != false) {
                $tpl->setVariable('WIDTH', 'width:100%;');
            }
        }
        if (array_key_exists(self::PROP_POSITION, $properties)) {
            switch ($properties[self::PROP_POSITION]) {
                case self::POSITION_CENTER:
                    $tpl->setVariable('CONTAINER_STYLE', 'text-align:center;');
                    break;
                case self::POSITION_RIGHT:
                    $tpl->setVariable('CONTAINER_STYLE', 'text-align:right;');
                    break;
                case self::POSITION_LEFT:
                default:
                    $tpl->setVariable('CONTAINER_STYLE', 'text-align:left;');
                    break;
            }
        }
    }

    /**
     * @param $key
     */
    public function txt($key): string
    {
        return ilOpenCastPlugin::getInstance()->txt('event_' . $key);
    }
}

