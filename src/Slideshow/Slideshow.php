<?php

namespace LgtToolkit\Slideshow;

use View;
use Exception;

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
            'useTheme' => true,
            'template' => null,
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
                'mobile' => true,
                'desktop' => true,
                'hd' => true,
            ],
            'showPagination' => [
                'mobile' => true,
                'desktop' => true,
                'hd' => true,
            ],
            'prevIcon' => null,
            'nextIcon' => null,
            'autoplay' => [
                'enabled' => false,
                'speed' => 6,
                'useTimer' => true,
            ],
        ];
    }

    public function stylesheet()
    {
        return 'slideshow/slideshow.css';
    }

    public function theme_stylesheet()
    {
        return 'slideshow/theme.css';
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

    public function getSlides(): array
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

    public function getArrayType(): string
    {
        if ($this->getSlides() === []) {
            return 'empty';
        }

        if (array_all($this->getSlides(), fn($value) => is_string($value))) {
            return 'string';
        }

        if (array_all($this->getSlides(), fn($value) => is_object($value))) {
            return 'object';
        }

        throw new Exception(t('Slide array must contain a single type of string[] or object[]'));
    }
}
