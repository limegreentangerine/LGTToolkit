<?php

namespace LgtToolkit\Providers\Express\Debugger;

use Core;
use LgtToolkit\DebugBar\Directors;
use Concrete\Core\Production\Modes;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Support\Facade\Application;

/**
 * Adds Express entity metadata to the application debug tools.
 */
class ExpressDebuggerService
{
    /**
     * Adds a debug collector entry for an Express entity.
     *
     * @param Entry $entry The Express entry to inspect.
     */
    public function debug(Entry $entry)
    {
        $express = $entry->getEntity();
        $app = Application::getFacadeApplication();
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle('lgt_toolkit');

        if (!$express || !$app || !$pkg) {
            return;
        }

        $config = $pkg->getFileConfig();
        $useDebug = $config->get('lgt_toolkit.debug') === true;

        $siteConfig = Core::make('config');
        $inDev = $siteConfig->get('concrete.security.production.mode') === Modes::MODE_DEVELOPMENT;

        $debugbar = $pkg->getDebugBar();
        if ($useDebug && $inDev) {
            $director = new Directors($app, $debugbar);
            $director->expressDebugging($express);
        } elseif ($inDev) {
            ob_start();
            echo '<script>';
            echo 'console.group(\'%c' . t('Available Attributes on Entity: %s', $express->getName()) . '\', \'color:red;font-size:14px\');';
            foreach ($express->getAttributes() as $attribute) {
                echo 'console.log(\'%c' . t('%s: can be access using getAttribute("%s") on this object. AttributeType: %s', $attribute->getAttributeKeyName(), $attribute->getAttributeKeyHandle(), $attribute->getAttributeTypeHandle()) . '\', \'color:blue\');';
            }
            foreach ($express->getAssociations() as $association) {
                echo 'console.log(\'%c' . t('An object association was found, this can be access using getAssociation("%s") on this object.', $association->getComputedTargetPropertyName()) . '\', \'color:green\');';
            }
            echo 'console.groupEnd();';
            echo '</script>';
            $html = ob_get_contents();
            ob_end_clean();

            echo $html;
        }
    }
}
