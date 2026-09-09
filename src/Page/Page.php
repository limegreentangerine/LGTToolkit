<?php

namespace LgtToolkit\Page;

use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Attribute\Controller as AttributeController;

class Page extends \Concrete\Core\Page\Page
{
    public function getAttributeController(string $handle): ?AttributeController
    {
        $attrKey = CollectionKey::getByHandle($handle);

        if (is_object($attrKey) && is_object($this) && !$this->isError()) {
            $attributeValue = $this->getAttributeValueObject($attrKey);

            if ($attributeValue) {
                $controller = $attributeValue->getController();
                return $controller;
            }
        }

        return null;
    }
}
