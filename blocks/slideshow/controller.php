<?php

namespace Concrete\Package\LgtToolkit\Block\Slideshow;

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btCacheBlockRecord = false;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;

    public function getBlockTypeName()
    {
        return t('Slideshow TESTING');
    }

    public function getBlockTypeDescription()
    {
        return t('Block for testing slideshow element.');
    }
}
