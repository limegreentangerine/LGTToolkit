<?php

namespace Concrete\Package\LgtToolkit\Block\LgtSection;

defined('C5_EXECUTE') or die('Access Denied.');

use File;
use Page;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btLgtSection';
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected $linkTypes = [
        '' => 'Choose link type...',
        0 => 'Internal',
        1 => 'External',
        2 => 'File Download',
        3 => 'Youtube Video',
        4 => 'Vimeo Video',
    ];
    protected $fileFilters = [
        'field' => 'type',
        'type' => [
            \Concrete\Core\File\Type\Type::T_IMAGE,
            \Concrete\Core\File\Type\Type::T_VIDEO,
        ],
    ];
    protected $internalLinkKeys = [
        0,
        2,
    ];
    protected int $media;
    protected string $linkType;
    protected int $gutterSize;
    protected string $titleSize;
    protected string $linkContent;
    protected string $backgroundColor;
    protected string $foregroundColor;

    protected function loadFormHelpers()
    {
        $this->set('th', $this->app->make('helper/text'));
        $this->set('ps', $this->app->make('helper/form/page_selector'));
        $this->set('al', $this->app->make('helper/concrete/asset_library'));
        $this->set('color', $this->app->make('helper/form/color'));
        $this->set('filters', $this->fileFilters);
        $this->set('linkTypes', $this->linkTypes);
        $this->set('headerSizes', $this->getHeaderSizes());
    }

    protected function processValues()
    {
        if (isset($this->linkType)) {
            $linkTypeHandle = $this->app->make('helper/text')->handle($this->linkTypes[$this->linkType]);
            $this->set($linkTypeHandle, $this->linkContent);
        }
    }

    protected function getFile(int $fID)
    {
        $file = File::getByID($fID);
        if ($file && !$file->isError()) {
            return $file;
        }

        return false;
    }

    protected function getPage(int $cID)
    {
        $page = Page::getByID($cID);

        if (is_object($page) && !$page->isError()) {
            return $page;
        }

        return false;
    }

    protected function getLinkContentURL()
    {
        switch ($this->linkType) {
            case 0:
                $page = $this->getPage($this->linkContent);
                if ($page) {
                    $this->set('page', $page);
                    return $page->getCollectionLink();
                }
                break;
            case 2:
                $file = $this->getFile($this->linkContent);
                if ($file) {
                    $this->set('download', $file);
                    return $file->getDownloadUrl();
                }
                break;
            default:
                return $this->linkContent;
                break;
        }

        return false;
    }

    protected function generateColourVariables()
    {
        $variablesString = '';
        if (isset($this->backgroundColor) && ($this->backgroundColor != null || $this->backgroundColor != '')) {
            $variablesString = $variablesString . '--backgroundColor:#' . ltrim($this->backgroundColor, '#') . ';';
        }

        if (isset($this->foregroundColor) && ($this->foregroundColor != null || $this->foregroundColor != '')) {
            $variablesString = $variablesString . '--foregroundColor:#' . ltrim($this->foregroundColor, '#') . ';';
        }

        return $variablesString;
    }

    public function getBlockTypeName()
    {
        return t('Section');
    }

    public function getBlockTypeDescription()
    {
        return t('Customisable content section.');
    }

    public function add()
    {
        $this->loadFormHelpers();
        $this->processValues();
    }

    public function edit()
    {
        $this->loadFormHelpers();
        $this->processValues();
    }

    public function view()
    {
        // display image variables
        $mediaFile = $this->getFile($this->media);
        $this->set('mediaFile', $mediaFile);

        // link variables
        $this->set('linkUrl', $this->getLinkContentURL());
        $this->set('target', ((in_array($this->linkType, $this->internalLinkKeys)) ? '_self' : '_blank'));

        // color variables
        $this->set('colors', $this->generateColourVariables());

        // arrangement variables
        $this->set('gutter', 'g-' . $this->gutterSize);

        // heading size
        $this->set('headingClass', sprintf('h%s', $this->titleSize));
    }

    public function save($args)
    {
        $linkTypeName = $this->linkTypes[$args['linkType']];
        if ($args['linkType'] !== '') {
            $linkTypeHandle = $this->app->make('helper/text')->handle($linkTypeName);
            $args['linkContent'] = $args[$linkTypeHandle];
        } else {
            $args['linkContent'] = '';
        }

        foreach ($this->linkTypes as $id => $name) {
            $handle = $this->app->make('helper/text')->handle($name);
            unset($args[$handle]);
        }

        parent::save($args);
    }

    public function getColumns(): array
    {
        $cols = [];

        for ($i = 1; $i <= 12; $i++) {
            $cols[$i] = $i;
        }

        return $cols;
    }

    public function getHeaderSizes(): array
    {
        $headerSizes = [];

        for ($i = 1; $i <= 6; $i++) {
            $headerSizes[$i] = t('Heading %s', $i);
        }

        return $headerSizes;
    }

    public function getContentSizes(): array
    {
        return [
            '' => t('Normal'),
            'lead' => t('Large'),
            'small' => t('Small'),
        ];
    }

    public function getButtonSizes(): array
    {
        return [
            'btn' => t('Normal'),
            'btn btn-lg' => t('Large'),
            'btn btn-sm' => t('Small'),
        ];
    }

    public function getButtonStyles(): array
    {
        return [
            'btn-primary' => t('Primary'),
            'btn-secondary' => t('Secondary'),
            'btn-light' => t('Light'),
            'btn-dark' => t('Dark'),
            'btn-success' => t('Success'),
            'btn-danger' => t('Danger'),
            'btn-text' => t('Text'),
        ];
    }

    public function getGutterSizes(): array
    {
        $gutters = [];

        for ($i = 0; $i <= 5; $i++) {
            $gutters[$i] = $i;
        }

        return $gutters;
    }

    public function getContentArrangementOptions(): array
    {
        return [
            'flex-column flex-lg-row' => t('Image > Content'),
            'flex-column flex-lg-row-reverse' => t('Content > Image'),
        ];
    }

    public function getHorizontalFlexClasses(): array
    {
        return [
            'justify-content-start' => t('Left'),
            'justify-content-center' => t('Center'),
            'justify-content-end' => t('Right'),
            'justify-content-around' => t('Space Around'),
            'justify-content-between' => t('Space Between'),
        ];
    }

    public function getVerticalFlexClasses(): array
    {
        return [
            'align-items-start' => t('Top'),
            'align-items-center' => t('Middle'),
            'align-items-end' => t('Bottom'),
            'align-items-stretch' => t('Stretch'),
        ];
    }
}
