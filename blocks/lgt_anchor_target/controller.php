<?php
namespace Concrete\Package\LgtToolkit\Block\LgtAnchorTarget;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btLgtAnchorTarget';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 550;

    public function getBlockTypeName()
    {
        return t('Anchor Target');
    }

    public function getBlockTypeDescription()
    {
        return t('Add an anchor link target to a page.');
    }

    public function view()
    {
        $this->set('c', Page::getCurrentPage());
    }

    public function save($args)
    {
        $th = $this->app->make('helper/text');
        $args['target'] = ($args['target'][0] === '#') ? str_replace('#', '', $th->handle($args['target'])) : $th->handle($args['target']);
        parent::save($args);
    }
}
