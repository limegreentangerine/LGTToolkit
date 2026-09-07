<?php
namespace Concrete\Package\LgtToolkit\Block\LgtContentSideTitle;

defined('C5_EXECUTE') or die('Access Denied.');

use Page;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;

class Controller extends BlockController
{
    protected $btTable = 'btLgtContentSideTitle';
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected int $link_cID;
    protected string $content;
    protected string $hex_background;
    protected string $gutter_size;

    public function getBlockTypeName()
    {
        return t('Content w/ Side Title');
    }

    public function getBlockTypeDescription()
    {
        return t('HTML/WYSIWYG Editor Content. With Side Title.');
    }

    public function getColumnSpacingOptions(): array
    {
        return [
            'justify-content-start'     => t('Left'),
            'justify-content-end'       => t('Right'),
            'justify-content-center'    => t('Center'),
            'justify-content-around'    => t('Space Around'),
            'justify-content-between'   => t('Space Between'),
        ];
    }

    public function getColumnArrangementOptions(): array
    {
        return [
            'flex-row'          => t('Title > Content'),
            'flex-row-reverse'  => t('Content > Title')
        ];
    }

    public function getColumnAlignmentOptions(): array
    {
        return [
            'align-items-center'    => t('Centre'),
            'align-items-start'     => t('Top'),
            'align-items-end'       => t('Bottom'),
            'align-items-stretch'   => t('Full Height'),
        ];
    }

    public function getColumnGutterSizeOptions(): array
    {
        $gutterSizes = [
            ''  => t('Default')
        ];

        for ($i = 0; $i <= 5; $i++) {
            $gutterSizes[$i] = $i;
        }

        return $gutterSizes;
    }

    public function getPage()
    {
        $page = Page::getByID($this->link_cID);

        if ($page !== null && !$page->isError()) {
            return $page;
        }

        return false;
    }

    public function add()
    {
        $this->set('color', $this->app->make('helper/form/color'));
        $this->set('ps', $this->app->make('helper/form/page_selector'));
    }

    public function edit()
    {
        $this->set('color', $this->app->make('helper/form/color'));
        $this->set('ps', $this->app->make('helper/form/page_selector'));
        $this->set('content', LinkAbstractor::translateFromEditMode($this->content));
    }

    public function view()
    {
        // Create background colour
        if (strlen($this->hex_background) > 0) {
            $background = ' style="background-color: #' . ltrim($this->hex_background, '#') . '"';
        } else {
            $background = '';
        }

        if (strlen($this->gutter_size)) {
            $gutter_size = 'g-' . $this->gutter_size;
        } else {
            $gutter_size = '';
        }

        $this->set('gutter_size', $gutter_size);
        $this->set('background', $background);
        $this->set('page', $this->getPage());
        $this->set('content', LinkAbstractor::translateFrom($this->content));
    }

    public function save($args)
    {
        $args['link_cID']   = $args['link_cID'] != '' ? $args['link_cID'] : 0;
        $args['content'] = LinkAbstractor::translateTo($args['content']);

        parent::save($args);
    }
}
