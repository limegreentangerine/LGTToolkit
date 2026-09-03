<?php

namespace LgtToolkit\Area;

use Punic\Data;
use Localization;
use Punic\Language;
use Concrete\Core\Multilingual\Page\Section\Section as MultilingualSection;

class GlobalArea extends \Concrete\Core\Area\GlobalArea
{
    public function __construct(string $arHandle)
    {
        $ms = MultilingualSection::getCurrentSection();
        $locale = is_object($ms) ? $ms->getLocale() : Localization::activeLocale();
        $fallbackLocale = Data::getFallbackLocale();
        if ($locale != $fallbackLocale) {
            $locName = Language::getName($locale, $fallbackLocale);
            $arHandle = $arHandle . ' ' . $locName;
        } else {
            $locName = Language::getName('en_GB');
            $arHandle = $arHandle . ' ' . $locName;
        }
        $this->arHandle = $arHandle;
    }
}
