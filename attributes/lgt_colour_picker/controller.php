<?php

namespace Concrete\Package\LgtToolkit\Attribute\LgtColourPicker;

use Concrete\Core\Attribute\DefaultController;
use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Entity\Attribute\Value\Value\TextValue;

class Controller extends DefaultController
{
    public $helpers = [
        'form',
    ];

    public function createAttributeValue(mixed $value): ?TextValue
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $attributeValue = new TextValue();
        $attributeValue->setValue($value);

        return $attributeValue;
    }

    public function form(): void
    {
        $this->set('value', is_object($this->attributeValue) ? $this->attributeValue->getValue() : '');
    }

    public function getDisplayValue(): string
    {
        if (!is_object($this->attributeValue)) {
            return '';
        }

        $value = $this->attributeValue->getValue();
        if (!$value) {
            return '';
        }

        return h($value);
    }

    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('eyedropper');
    }

    public function searchForm(mixed $list): mixed
    {
        $value = $this->request('value');

        if ($value) {
            $list->filterByAttribute($this->attributeKey->getAttributeKeyHandle(), $value);
        }

        return $list;
    }

    public function search()
    {
        echo $this->app->make('helper/form')->color(
            $this->field('value'),
            $this->request('value'),
        );
    }
}
