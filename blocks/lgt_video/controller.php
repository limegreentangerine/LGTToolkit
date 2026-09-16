<?php

namespace Concrete\Package\LgtToolkit\Block\LgtVideo;

defined('C5_EXECUTE') or die('Access Denied.');

use File;
use Page;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btLgtVideo';
    protected $btDefaultSet = 'multimedia';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected $videoFilters = [];
    protected int $videofID;
    protected int $posterfID;
    protected bool $square;
    protected bool $controls;
    protected bool $autoplay;
    protected bool $muted;

    protected function getFileObject(int $fID)
    {
        $file = File::getByID($fID);

        if ($file !== null && !$file->isError()) {
            return $file;
        }

        return false;
    }

    public function getBlockTypeName()
    {
        return t('Custom Video');
    }

    public function getBlockTypeDescription()
    {
        return t('Create a video player with multiple custom options.');
    }

    public function add()
    {
        $this->set('al', $this->app->make('helper/concrete/asset_library'));
    }

    public function edit()
    {
        $this->set('al', $this->app->make('helper/concrete/asset_library'));
    }

    public function view()
    {
        $this->set('c', Page::getCurrentPage());
        $this->set('video', $this->getFileObject($this->videofID));
        $this->set('poster', $this->getFileObject($this->posterfID));
    }

    public function save($args)
    {
        $args['autoplay'] = isset($args['autoplay']) ? 1 : 0;
        $args['muted'] = isset($args['muted']) ? 1 : 0;
        $args['controls'] = isset($args['controls']) ? 1 : 0;
        $args['square'] = isset($args['square']) ? 1 : 0;
        parent::save($args);
    }

    public function getVideoFilters()
    {
        return $this->videoFilters;
    }

    public function isSquare(): bool
    {
        return $this->square;
    }

    public function showControls(): bool
    {
        return $this->controls;
    }

    public function autoplay(): bool
    {
        return $this->autoplay;
    }

    public function muted(): bool
    {
        return $this->muted;
    }
}
