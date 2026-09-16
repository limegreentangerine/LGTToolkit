<?php

namespace Concrete\Package\LgtToolkit\Block\LgtSpacer;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btLgtSpacer';
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;

    public function getBlockTypeName()
    {
        return t('Spacer');
    }

    public function getBlockTypeDescription()
    {
        return t('Create space between blocks on a page.');
    }

    public function view()
    {
        $this->set('c', Page::getCurrentPage());
    }
}
