<?php

namespace LgtToolkit\Package;

use Core;
use Concrete\Core\Entity\Package;
use Concrete\Core\Attribute\TypeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Attribute\Key\Category;
use Concrete\Core\Attribute\Set as AttributeSet;
use Concrete\Core\Attribute\Type as AttributeType;
use Concrete\Core\Entity\Attribute\Set as AttributeSetEntity;
use Concrete\Core\Entity\Attribute\Value\Value\SelectValueOption;

trait AttributeTrait
{
    /**
     * Add Attribute Set
     *
     * @param string  $categoryHandle
     * @param string  $setHandle
     * @param string  $setName
     * @param Package $pkg
     *
     * @return object AttributeSet
     */
    protected function addAttributeSet(string $categoryHandle, string $setHandle, string $setName, Package $pkg): object
    {
        $category = Category::getByHandle($categoryHandle);
        $category->setAllowAttributeSets(Category::ASET_ALLOW_MULTIPLE);

        $set = AttributeSet::getByHandle($setHandle);
        if (!is_object($set)) {
            $set = $category->addSet($setHandle, t($setName), $pkg);
        }

        return $set;
    }

    /**
     * Get Attribute Set
     *
     * @param string $handle
     *
     * @return ?object AttributeSet
     */
    protected function getAttributeSet(string $handle): ?object
    {
        $set = AttributeSet::getByHandle($handle);
        return $set ?? null;
    }

    /**
     * Add Attribute
     *
     * @param string              $handle
     * @param string              $name
     * @param string              $type
     * @param string              $categoryKeyObject
     * @param ?AttributeSetEntity $attributeSetObject
     * @param Package             $pkg
     * @param bool                $selectAllowOtherValues
     *
     * @return object
     */
    protected function addAttribute(string $handle, string $name, string $type, string $categoryKeyObject, ?AttributeSetEntity $attributeSetObject, Package $pkg, bool $selectAllowOtherValues = true): object
    {
        $attr = $categoryKeyObject::getByHandle($handle);
        if (!is_object($attr)) {
            $info = [
                'akHandle' => $handle,
                'akName' => $name,
                'akIsSearchable' => true,
            ];
            $att_type = AttributeType::getByHandle($type);
            $attr = $categoryKeyObject::add($att_type, $info, $pkg);

            if ($attributeSetObject) {
                $attr->setAttributeSet($attributeSetObject);
                $entityManager = Core::make(EntityManagerInterface::class);
                $entityManager->persist($attributeSetObject);
                $entityManager->flush();
            }

            if ($type == 'select' && $selectAllowOtherValues == true) {
                $attr->getController()->setAllowOtherValues();
            }
        }

        return $attr;
    }

    /**
     * Add Select attribute with options
     *
     * @param string       $handle
     * @param string       $name
     * @param array        $optionList
     * @param object       $categoryKeyObject
     * @param AttributeSet $attributeSetObject
     * @param Package      $pkg
     * @param bool         $allowOtherValues
     * @param bool         $hideNoneOption
     * @param bool         $allowMultipleValues
     *
     * @return object AttributeType
     */
    protected function addSelectAttribute(string $handle, string $name, array $optionList, object $categoryKeyObject, AttributeSet $attributeSetObject, Package $pkg, bool $allowOtherValues = false, bool $hideNoneOption = true, bool $allowMultipleValues = false): object
    {
        $attr = $categoryKeyObject::getByHandle($handle);

        if (!is_object($attr)) {
            $info = [
                'akHandle' => $handle,
                'akName' => $name,
                'akIsSearchable' => true,
            ];
            $att_type = AttributeType::getByHandle('select');

            $options = [];
            $displayOrder = 0;
            foreach ($optionList as $option) {
                $opt = new SelectValueOption();
                $opt->setSelectAttributeOptionValue($option);
                $opt->setDisplayOrder($displayOrder);
                if (is_object($opt)) {
                    $options[] = $opt;
                    ++$displayOrder;
                }
            }

            $attr = $categoryKeyObject::add($att_type, $info, $pkg);

            if ($attributeSetObject) {
                // Deprecated? Not working?
                $attr->setAttributeSet($attributeSetObject);

                $entityManager = Core::make(EntityManagerInterface::class);
                $entityManager->persist($attributeSetObject);
                $entityManager->flush();
            }

            $info = [
                'akHandle' => $handle,
                'akName' => $name,
                'akIsSearchable' => true,
                'akSelectAllowMultipleValues' => $allowMultipleValues,
                'akSelectAllowOtherValues' => $allowOtherValues,
                'akHideNoneOption' => $hideNoneOption,
            ];
            $attr->getController()->setOptions($options);
            $attr->getController()->saveKey($info);
        }

        return $attr;
    }
    /**
     * Add Attribute Type
     *
     * @param string  $handle
     * @param string  $name
     * @param Package $pkg
     * @param array   $sets
     *
     * @return object Type
     */
    public function addAttributeType(string $handle, string $name, Package $pkg, array $sets = ['collection']): object
    {
        $factory = Core::make(TypeFactory::class);
        $type = $factory->getByHandle($handle);

        if (!is_object($type)) {
            $type = $factory->add($handle, t($name), $pkg);
        }

        foreach ($sets as $sh) {
            $col = Category::getByHandle($sh);
            $col->associateAttributeKeyType($type);
        }

        return $type;
    }
}
