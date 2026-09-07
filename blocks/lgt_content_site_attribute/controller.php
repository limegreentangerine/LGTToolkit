<?php

namespace Concrete\Package\LgtToolkit\Block\LgtContentSiteAttribute;

defined('C5_EXECUTE') or die('Access Denied.');

use AttributeSet;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;

class Controller extends BlockController
{
    protected $btTable = 'btContentLocal'; // We are just going to hook into the normal content table
    protected $btDefaultSet = 'basic';
    protected $btInterfaceWidth = 800;
    protected $btInterfaceHeight = 600;
    protected string $content;

    protected function addressFormatter(object $address)
    {
        $valueData = [
            'address1' => $address->address1,
            'address2' => $address->address2,
            'city' => $address->city,
            'state_province' => $address->state_province,
            'postal_code' => $address->postal_code,
            'country' => $address->getFullCountry(),
        ];

        foreach ($valueData as $key => $value) {
            if ($value == '') {
                unset($valueData[$key]);
            }
        }

        return implode(', ', $valueData);
    }

    public function getBlockTypeName()
    {
        return t('Content w/ Site Attribute');
    }

    public function getBlockTypeDescription()
    {
        return t('HTML/WYSIWYG Editor Content. Can automatically substitute in Attribtues from the Site Attributes Set e.g. {site_attribute_handle}.');
    }

    public function getContentForDisplay()
    {
        $sh = $this->app->make('site')->getSite();
        $as = AttributeSet::getByHandle('site_attributes');

        foreach ($as->getAttributeKeys() as $ak) {
            $attrHandle = $ak->getAttributeType()->getAttributeTypeHandle();


            switch ($attrHandle) {
                case 'address':
                    $search[] = '{' . $ak->getAttributeKeyHandle() . '}';
                    $replace[] = $this->addressFormatter($sh->getAttribute($ak->getAttributeKeyHandle()));
                    break;
                case 'image_file':
                    $search[] = '{' . $ak->getAttributeKeyHandle() . '}';
                    $fileAttr = $sh->getAttribute($ak->getAttributeKeyHandle());
                    if ($fileAttr !== null) {
                        $replace[] = $fileAttr->getRelativePath();
                    } else {
                        $replace[] = $sh->getAttribute($ak->getAttributeKeyHandle());
                    }
                    break;
                default:
                    $search[] = '{' . $ak->getAttributeKeyHandle() . '}';
                    $replace[] = $sh->getAttribute($ak->getAttributeKeyHandle());
                    break;
            }
        }

        $content = str_replace($search ?? [], $replace ?? [], $this->content);

        $content = LinkAbstractor::translateFrom($content);

        return $content;
    }

    public function add()
    {
        $this->loadFormData();
    }

    public function edit()
    {
        $this->loadFormData();
        $this->set('content', LinkAbstractor::translateFromEditMode($this->content));
    }

    public function loadFormData()
    {
        // Grab site_attribute set
        $as = AttributeSet::getByHandle('site_attributes');
        $token = $this->app->make('token');


        $content_options = [
            'cookie' => 'Cookie Policy',
            'privacy' => 'Privacy Policy',
            'accessibility' => 'Accessibility Statement',
        ];

        $form_data = [
            'content_options' => $content_options,
        ];

        $this->set('as', $as);
        $this->set('token', $token);
        $this->set('form_data', $form_data);
    }

    public function view()
    {
        $this->set('content', $this->getContentForDisplay());
    }

    public function save($args)
    {
        $args['content'] = LinkAbstractor::translateTo($args['content']);

        parent::save($args);
    }
}
