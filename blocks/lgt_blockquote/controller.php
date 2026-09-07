<?php
namespace Concrete\Package\LgtToolkit\Block\LgtBlockquote;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;

class Controller extends BlockController
{
    protected $btTable = 'btLgtBlockquote';
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected string $quote;
    protected string $hex_background;
    protected string $hex_text;

    public function getBlockTypeName()
    {
        return t('Blockquote with Author');
    }

    public function getBlockTypeDescription()
    {
        return t('Add a blockquote with author.');
    }

    public function add()
    {
        $this->loadFormData();
    }

    public function edit()
    {
        $this->loadFormData();
        $this->set('quote', LinkAbstractor::translateFromEditMode($this->quote));
    }

    public function loadFormData()
    {
        $color = $this->app->make('helper/form/color');
        $this->set('color', $color);
    }

    public function view()
    {
        // Create background colour
        if (strlen($this->hex_background) > 0) {
            $background = ltrim($this->hex_background, '#');
        } else {
            $background = '000000';
        }

        $this->set('background', 'background: #' . $background . ';');

        // Create text colour
        if (strlen($this->hex_text) > 0) {
            $colour = ltrim($this->hex_text, '#');
        } else {
            $colour = 'ffffff';
        }

        $this->set('colour', 'color: #' . $colour . ';');
        $this->set('quote', LinkAbstractor::translateFrom($this->quote));
    }

    public function save($args)
    {
        $arg['quote'] = LinkAbstractor::translateTo($args['quote']);
        parent::save($args);
    }
}
