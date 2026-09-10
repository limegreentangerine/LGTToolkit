<?php

namespace Concrete\Package\LgtToolkit\Attribute\LgtPageRedirector;

use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Attribute\Controller as AttributeController;
use Concrete\Package\LgtToolkit\Entity\Attribute\Value\Value\RedirectValue;

class Controller extends AttributeController
{
    public $helpers = [
        'form',
        'form/page_selector',
    ];

    protected function addAssets(): void
    {
        $pkg = $this->app->make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');
        $this->addFooterItem(
            $this->app->make('helper/html')->javascript(
                $pkg->getRelativePath() . '/attributes/lgt_page_redirector/js/form.js',
            ),
        );
    }

    public function getRedirectTypes(): array
    {
        return [
            'page' => 'Page',
            'external' => 'External URL',
        ];
    }

    public function getRedirectMethods(): array
    {
        return [
            '302' => 'Temporary Redirect (302) - Recommended',
            '301' => 'Permanent Redirect (301)',
        ];
    }

    public function createAttributeValueFromRequest()
    {
        $value = match ($this->post('redirectType')) {
            'external' => $this->post('externalValue'),
            'page' => $this->post('pageValue'),
        };

        $redirect = new RedirectValue();
        $redirect->setRedirectMethod($this->post('redirectMethod'));
        $redirect->setRedirectType($this->post('redirectType'));
        $redirect->setValue($value);

        return $redirect;
    }

    public function getAttributeValueClass()
    {
        return RedirectValue::class;
    }

    public function form(): void
    {
        $redirectType = '';
        $redirectMethod = '';
        $value = '';

        if ($this->attributeValue) {
            $redirect = $this->attributeValue->getValueObject();

            if ($redirect instanceof RedirectValue) {
                $redirectType = $redirect->getRedirectType();
                $redirectMethod = $redirect->getRedirectMethod();
                $value = $redirect->getValue();
            }
        }

        $this->set('redirectType', $redirectType);
        $this->set('redirectMethod', $redirectMethod);
        $this->set('value', $value);

        $this->addAssets();
    }

    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('map-signs');
    }
}
