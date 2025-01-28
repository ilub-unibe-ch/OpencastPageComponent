<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see https://github.com/ILIAS-eLearning/ILIAS/tree/trunk/docs/LICENSE */

namespace srag\Plugins\OpencastPageComponent\Config;

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ilOpencastPageComponentPlugin;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ConfigForm
{
    /**
     * @var mixed[]
     */
    private array $components = [];
    /**
     * @var \ilCtrlInterface
     */
    private $ctrl;
    /**
     * @var Factory
     */
    private $ui_factory;
    /**
     * @var Renderer
     */
    private $ui_renderer;
    private \ilOpencastPageComponentPlugin $plugin;
    /**
     * @var \ILIAS\Refinery\Factory
     */
    private $refinery;
    private \ilOpencastPageComponentConfigGUI $calling_gui;
    private ConfigRepository $config_repository;
    /**
     * @var Standard
     */
    private $form;

    public function __construct(
        \ilOpencastPageComponentConfigGUI $calling_gui,
        string $command,
        ConfigRepository $config_repository
    ) {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->refinery = $DIC->refinery();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->plugin = ilOpencastPageComponentPlugin::getInstance();

        $this->config_repository = $config_repository;
        $this->calling_gui = $calling_gui;
        $this->initForm($command);
    }

    private function getLocaleString(string $key): string
    {
        return $this->plugin->txt('config_' . $key);
    }

    private function initForm(string $command): void
    {
        $inputs = [];

        $ff = $this->ui_factory->input()->field();

        $inputs[] = $ff
            ->numeric(
                $this->getLocaleString(Config::KEY_DEFAULT_WIDTH)
                // , $this->getLocaleString(Config::KEY_DEFAULT_WIDTH . '_info')
            )
            ->withValue(
                (int) $this->config_repository->get(Config::KEY_DEFAULT_WIDTH, 640)->getValue()
            )
            ->withRequired(true)
            ->withAdditionalTransformation(
                $this->refinery->custom()->transformation(fn ($value): Config => $this->config_repository->store(
                    new Config(Config::KEY_DEFAULT_WIDTH, (int) $value)
                ))
            );

        $inputs[] = $ff
            ->numeric(
                $this->getLocaleString(Config::KEY_DEFAULT_HEIGHT)
                // , $this->getLocaleString(Config::KEY_DEFAULT_HEIGHT . '_info')
            )->withValue(
                (int) $this->config_repository->get(Config::KEY_DEFAULT_HEIGHT, 480)->getValue()
            )
            ->withRequired(true)
            ->withAdditionalTransformation(
                $this->refinery->custom()->transformation(fn ($value): Config => $this->config_repository->store(
                    new Config(Config::KEY_DEFAULT_HEIGHT, (int) $value)
                ))
            );

        $inputs[] = $ff
            ->checkbox(
                $this->getLocaleString(Config::KEY_DEFAULT_AS_LINK)
                // , $this->getLocaleString(Config::KEY_DEFAULT_AS_LINK . '_info')
            )->withValue(
                (bool) $this->config_repository->get(Config::KEY_DEFAULT_AS_LINK, false)->getValue()
            )
            ->withAdditionalTransformation(
                $this->refinery->custom()->transformation(fn ($value): Config => $this->config_repository->store(
                    new Config(Config::KEY_DEFAULT_AS_LINK, (bool) $value)
                ))
            );

        $post_url = $this->ctrl->getFormAction($this->calling_gui, $command);

        $this->components[] = $this->form = $this->ui_factory->input()->container()->form()->standard(
            $post_url,
            $inputs
        );
    }

    public function save(ServerRequestInterface $request): bool
    {
        return $this->form->withRequest($request)->getData() !== null;
    }

    public function getHTML(): string
    {
        return $this->ui_renderer->render($this->components);
    }
}
