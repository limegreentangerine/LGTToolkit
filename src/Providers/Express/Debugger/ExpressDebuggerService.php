<?php

namespace LgtToolkit\Providers\Express\Debugger;

use Package;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Support\Facade\Application;
use DebugBar\DataCollector\MessagesCollector;

class ExpressDebuggerService
{
    public function debug(Entry $entry)
    {
        $express = $entry->getEntity();
        $app = Application::getFacadeApplication();
        $pkg = Package::getByHandle('lgt-toolkit');
        $config = $pkg->getFileConfig();

        if ($config->get('lgt_toolkit.debug') === true && \Core::make('config')->get('concrete.security.production.mode') === \Concrete\Core\Production\Modes::MODE_DEVELOPMENT) {
            $debugBar = $app->make('debugbar');
            $debugBar->addCollector(new MessagesCollector($express->getHandle()));
            $debugBar[$express->getHandle()]->info(t('Available Attributes on Entity: %s', $express->getName()));
            foreach ($express->getAttributes() as $attribute) {
                $debugBar[$express->getHandle()]->info(t('%s: can be access using getAttribute("%s") on this object. AttributeType: %s', $attribute->getAttributeKeyName(), $attribute->getAttributeKeyHandle(), $attribute->getAttributeTypeHandle()));
            }
            foreach ($express->getAssociations() as $association) {
                $debugBar[$express->getHandle()]->info(t('An object association was found, this can be access using getAssociation("%s") on this object.', $association->getComputedTargetPropertyName()));
            }
            $debugBar['messages']->aggregate($debugBar[$express->getHandle()]);
        }

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
