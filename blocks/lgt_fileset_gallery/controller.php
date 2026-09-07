<?php

namespace Concrete\Package\Lgttoolkit\Block\LgtFilesetGallery;

defined('C5_EXECUTE') or die('Access Denied.');

use FileSet;
use Concrete\Core\Block\BlockController;
use Concrete\Core\File\Set\SetList as FileSetList;

class Controller extends BlockController
{
    protected $btTable = 'btLgtFilesetGallery';
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected int $fsID;

    protected function getFileSets()
    {
        $fileSets = [];

        $fsl = new FileSetList();
        $fsl->filterByType(FileSet::TYPE_PUBLIC);
        $sets = $fsl->get();

        foreach ($sets as $set) {
            $fileSets[$set->getFileSetID()] = $set->getFileSetName();
        }

        return $fileSets;
    }

    protected function getFilesFromSet(int $fsID)
    {
        $fs = FileSet::getByID($fsID);
        if (is_object($fs)) {
            return $fs->getFiles();
        }

        return false;
    }

    public function getBlockTypeName()
    {
        return t('Fileset Gallery');
    }

    public function getBlockTypeDescription()
    {
        return t('Create and add a fileset gallery to a page.');
    }

    public function add()
    {
        $this->set('filesets', $this->getFileSets());
    }

    public function edit()
    {
        $this->set('filesets', $this->getFileSets());
    }

    public function view()
    {
        $this->set('files', $this->getFilesFromSet($this->fsID));
    }
}
