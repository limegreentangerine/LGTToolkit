<?php

namespace LgtToolkit\Slideshow;

use View;

final readonly class Slideshow
{
    protected View $view;
    protected Options $options;

    public function __construct(
        public array $slides,
        public array $settings,
    ) {
        $this->view = new View();
        $settings = array_merge($this->getDefaultSettings(), $settings);
        $this->options = Options::fromArray($settings);
    }

    private function getDefaultSettings()
    {
        return [
            'mobile' => 1,
            'desktop' => 1,
            'hd' => 1,
            'draggable' => false,
            'snap' => 'start',
            'gap' => [
                'mobile' => 0,
                'desktop' => 0,
                'hd' => 0,
            ],
            'peek' => [
                'mobile' => 0,
                'desktop' => 0,
                'hd' => 0,
            ],
            'showButtons' => [
                'mobile' => false,
                'desktop' => true,
                'hd' => true,
            ],
            'showPagination' => [
                'mobile' => true,
                'desktop' => false,
                'hd' => false,
            ],
            'prevIcon' => null,
            'nextIcon' => null,
            'autoplay' => null,
        ];
    }

    public function stylesheet()
    {
        return 'slideshow.css';
    }

    public function javascript()
    {
        return 'slideshow.js';
    }

    public function getView()
    {
        return $this->view;
    }

    public function getOptions($asString = false): array|string
    {
        return ($asString === true) ? json_encode($this->options->toArray()) : $this->options->toArray();
    }

    public function getOption(string $handle)
    {
        return $this->getOptions()[$handle] ?? null;
    }

    public function getSlides()
    {
        return $this->slides ?? [];
    }

    public function generateStyleVariables()
    {
        $styles = '';

        $styles = $styles . '--mobile:' . $this->getOption('mobile') . ';';
        $styles = $styles . '--desktop:' . $this->getOption('desktop') . ';';
        $styles = $styles . '--hd:' . $this->getOption('hd') . ';';
        $styles = $styles . '--snap:' . $this->getOption('snap') . ';';

        return $styles;
    }
}
