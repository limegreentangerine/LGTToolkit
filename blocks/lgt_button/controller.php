<?php
namespace Concrete\Package\LgtToolkit\Block\LgtButton;

defined('C5_EXECUTE') or die('Access Denied.');

use File;
use Page;
use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = 'btLgtButton';
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 400;
    protected $btInterfaceHeight = 550;
    protected bool $is_external;
    protected string $external_url;
    protected int $page_cID;
    protected int $fID;
    protected string $link_type;
    protected string $anchor;
    protected bool $full_width;
    protected string $style;
    protected string $button_size;

    public function getBlockTypeName()
    {
        return t('Button');
    }

    public function getBlockTypeDescription()
    {
        return t('Adds a button to a page.');
    }

    public function getStyles(): array
    {
        return [
            'primary'           => 'Primary',
            'secondary'         => 'Secondary',
            'success'           => 'Success',
            'danger'            => 'Danger',
            'warning'           => 'Warning',
            'info'              => 'Info',
            'light'             => 'Light',
            'dark'              => 'Dark',
            'link'              => 'Link',
            'outline-primary'   => 'Outline Primary',
            'outline-secondary' => 'Outline Secondary',
            'outline-success'   => 'Outline Success',
            'outline-danger'    => 'Outline Danger',
            'outline-warning'   => 'Outline Warning',
            'outline-info'      => 'Outline Info',
            'outline-light'     => 'Outline Light',
            'outline-dark'      => 'Outline Dark'
        ];
    }

    public function getAnchors()
    {
        $anchors = [];
        $c = Page::getCurrentPage();
        $blocks = $c->getBlocks();

        foreach ($blocks as $block) {
            if ($block->getBlockTypeHandle() == 'anchor_target') {
                $instance = $block->getInstance();
                $anchors['#' . $instance->target] = '#' . $instance->target;
            }
        }

        return $anchors;
    }

    public function getLinkTypes()
    {
        $types = [
            'internal' => 'Another Page',
            'external' => 'External URL',
            'file' => 'File'
        ];

        if (count($this->getAnchors()) > 0) {
            $types['anchor'] = 'Page Anchor';
        }

        return $types;
    }

    public function add()
    {
        $this->set('ps', $this->app->make('helper/form/page_selector'));
        $this->set('al', $this->app->make('helper/concrete/asset_library'));
    }

    public function edit()
    {
        $this->set('ps', $this->app->make('helper/form/page_selector'));
        $this->set('al', $this->app->make('helper/concrete/asset_library'));
    }

    public function save($args)
    {
        $args['full_width']     = (isset($args['full_width']) && $args['full_width'] != '') ? $args['full_width'] : 0;
        $args['new_window']     = (isset($args['new_window'])) ? 1 : 0;
        $args['is_external']    = (isset($args['is_external']) && $args['is_external'] != '') ? $args['is_external'] : (($args['link_type'] == 'external') ? 1 : 0);

        if ($args['is_external'] == 1) {
            $needles = ['http://', 'https://', 'mailto:', 'tel:'];
            $found = false;

            foreach ($needles as $needle) {
                if (stripos($args['external_url'], $needle) !== false) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $args['external_url'] = 'http://' . $args['external_url'];
            }
        }

        parent::save($args);
    }

    public function view()
    {
        // Generate link
        $link = '';
        if ($this->is_external == 0) {
            if ($this->page_cID > 0) {
                $page = Page::getByID($this->page_cID);
                if (!$page->isError()) {
                    $link = $page->getCollectionLink();
                }
            }
        } else if ($this->is_external == 1) {
            $link = $this->external_url;
        }

        // new link logic - Lee: 15/06/2020
        if (strlen($link) < 1) {
            $link = '#';
            if ($this->link_type == 'internal') {
                $page = Page::getByID($this->page_cID);
                if (is_object($page) && !$page->isError()) {
                    $link = $page->getCollectionLink();
                }
            } else if ($this->link_type == 'external') {
                $link = $this->external_url;
            } else if ($this->link_type == 'file') {
                $file = File::getByID($this->fID);
                if (is_object($file) && !$file->isError()) {
                    $link = $file->getDownloadUrl();
                }
            } else if ($this->link_type == 'anchor') {
                $link = $this->anchor;
            }
        }

        $this->set('link', $link);

        // Generate Style
        $styles = ['btn'];

        if ($this->full_width) {
            $styles[] = 'btn-block';
        }

        if ($this->style) {
            $styles[] = 'btn-' . $this->style;
        }

        if ($this->button_size) {
            $styles[] = $this->button_size;
        }

        if ($this->link_type == 'anchor') {
            $styles[] = 'anchor-button';
        }

        $this->set('button_style', $styles);
    }
}
