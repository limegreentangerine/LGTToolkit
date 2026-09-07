<?php
namespace Concrete\Package\LgtToolkit\Block\LgtAnchorMenu;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Stack\Stack;

class Controller extends BlockController
{
    protected $btTable = 'btLgtAnchorMenu';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 550;

    public function getBlockTypeName()
    {
        return t('Anchor Menu');
    }

    public function getBlockTypeDescription()
    {
        return t('Add an anchor menu display to a page.');
    }

    public function view()
    {
        $c = Page::getCurrentPage();

        $this->set('c', $c);
        $this->set('navigation', $this->generateNavigation($c));
    }

    protected function generateNavigation(Page $c)
    {
        $navigation = [];
        $blocks = $c->getBlocks();
        $th = $this->app->make('helper/text');

        foreach ($blocks as $block) {
            if ($block->getBlockTypeHandle() == 'lgt_anchor_target') {
                $blockController = $block->getController();
                $target = $blockController->target;
                $name   = (isset($blockController->linkText) && strlen($blockController->linkText) > 0) ? $blockController->linkText : $th->unhandle($target);
                $navigation[$block->getBlockDisplayOrder()] = [
                    'target'    => $target,
                    'name'      => $name
                ];
            } else if ($block->getBlockTypeHandle() == 'core_stack_display') {
                $stackId = $block->getInstance()->getStackID();
                $stack = Stack::getByID($stackId);
                foreach ($stack->getBlocks() as $b) {
                    if ($b->getBlockTypeHandle() == 'lgt_anchor_target') {
                        $blockController = $b->getController();
                        $target = $blockController->get('target');
                        $name   = (isset($blockController->linkText) && strlen($blockController->get('linkText')) > 0) ? $blockController->get('linkText') : $th->unhandle($target);
                        $navigation[$b->getBlockDisplayOrder()] = [
                            'target'    => $target,
                            'name'      => $name
                        ];
                    }
                }
            }
        }

        ksort($navigation);

        return $navigation;
    }
}
