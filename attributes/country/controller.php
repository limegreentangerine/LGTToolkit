<?php

namespace Concrete\Package\LgtToolkit\Attribute\Country;

use Core;
use Concrete\Core\Attribute\DefaultController;
use Concrete\Core\Localization\Service\CountryList;
use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Entity\Attribute\Value\Value\TextValue;

class Controller extends DefaultController
{
    public $helpers = [
        'form',
    ];

    public function getCountries(): array
    {
        return Core::make(CountryList::class)->getCountries();
    }

    public function createAttributeValue(mixed $value): ?TextValue
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $countries = $this->getCountries();

        if (!isset($countries[$value])) {
            return null;
        }

        $attributeValue = new TextValue();
        $attributeValue->setValue($value);

        return $attributeValue;
    }

    public function form(): void
    {
        $this->set('countries', $this->getCountries());
        $this->set('value', is_object($this->attributeValue) ? $this->attributeValue->getValue() : '');
    }

    public function getDisplayValue(): string
    {
        if (!is_object($this->attributeValue)) {
            return '';
        }

        $code = $this->attributeValue->getValue();
        if (!$code) {
            return '';
        }

        $countryList = $this->app->make(CountryList::class);
        return h($countryList->getCountryName($code));
    }

    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('flag');
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
        echo $this->app->make('helper/form')->select(
            $this->field('value'),
            [
                '' => t('Any Country'),
            ] + $this->getCountries(),
            $this->request('value'),
        );
    }
}
