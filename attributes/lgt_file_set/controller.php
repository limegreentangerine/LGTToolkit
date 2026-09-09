<?php

namespace Concrete\Package\LgtToolkit\Attribute\LgtFileSet;

use FileSet;
use Concrete\Core\Attribute\DefaultController;
use Concrete\Core\File\Set\SetList as FileSetList;
use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Entity\Attribute\Value\Value\TextValue;

class Controller extends DefaultController
{
    public $helpers = [
        'form',
    ];

    public function getFileSets(): array
    {
        $sets = [];
        $fsl = new FileSetList();
        $fsl->filterByType(FileSet::TYPE_PUBLIC);
        $fileSets = $fsl->get();

        foreach ($fileSets as $s) {
            $sets[$s->getFileSetID()] = $s;
        }

        return $sets;
    }

    public function createAttributeValue(mixed $value): ?TextValue
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $sets = $this->getFileSets();

        if (!isset($sets[$value])) {
            return null;
        }

        $attributeValue = new TextValue();
        $attributeValue->setValue($value);

        return $attributeValue;
    }

    public function form(): void
    {
        $this->set('filesets', $this->getFileSets());
        $this->set('value', is_object($this->attributeValue) ? $this->attributeValue->getValue() : '');
    }

    public function getDisplayValue(): ?FileSet
    {
        if (!is_object($this->attributeValue)) {
            return null;
        }

        $fsID = $this->attributeValue->getValue();
        if (!$fsID) {
            return null;
        }

        $set = FileSet::getByID($fsID);
        return $set ?? null;
    }

    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('folder-open');
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
                '' => t('Any Fileset'),
            ] + $this->getFileSets(),
            $this->request('value'),
        );
    }
}
