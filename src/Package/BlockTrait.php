<?php

namespace LgtToolkit\Package;

use BlockType;
use BlockTypeSet;
use Concrete\Core\Page\Type\Composer\Control\BlockControl;

trait BlockTrait
{
    /**
     * Add Block Type
     *
     * @param  string                                          $handle
     * @param  \Concrete\Core\Entity\Package                   $pkg
     * @return \Concrete\Core\Entity\Block\BlockType\BlockType
     */
    protected function addBlockType(string $handle, \Concrete\Core\Entity\Package $pkg): \Concrete\Core\Entity\Block\BlockType\BlockType
    {
        $bt = BlockType::getByHandle($handle);
        !is_object($bt) ? $bt = BlockType::installBlockType($handle, $pkg) : $bt->refresh();
        return $bt;
    }

    /**
     * Auto Install Blocks
     *
     * Searches package block directory for block types, installs them
     * and also adds a BlockTypeSet using the package details
     *
     * @param \Concrete\Core\Entity\Package $pkg
     */
    protected function autoInstallBlocks(\Concrete\Core\Entity\Package $pkg)
    {
        $ignorePaths = [ '.gitkeep', '..', '.' ];
        $dirPath = sprintf('%s/blocks', $pkg->getPackagePath());

        $blockDirectory = array_diff(scandir($dirPath), $ignorePaths);

        if (!empty($blockDirectory)) {
            $this->addBlockTypeSet($pkg->getPackageHandle(), $pkg->getPackageName(), $pkg);

            foreach ($blockDirectory as $blockHandle) {
                $isBlock = file_exists(sprintf('%s/blocks/%s/controller.php', $pkg->getPackagePath(), $blockHandle));
                if ($isBlock) {
                    $this->addBlockType($blockHandle, $pkg);
                }
            }
        }
    }

    /**
     * Install Blocks from Array
     *
     * installs Blocks from an array of handles,
     * and also adds a BlockTypeSet using the package details
     *
     * @param \Concrete\Core\Entity\Package $pkg
     */
    protected function installBlockFromArray(array $blocks, \Concrete\Core\Entity\Package $pkg)
    {
        if (count($blocks) > 0) {
            $this->addBlockTypeSet($pkg->getPackageHandle(), $pkg->getPackageName(), $pkg);

            foreach ($blocks as $blockHandle) {
                $this->addBlockType($blockHandle, $pkg);
            }
        }
    }

    /**
     * Adds a Block Form Control
     *
     * @param string $blockHandle       Block Type Handle
     * @param object $layoutSet         Composer Layout Set
     * @param string $customName        Custom Name for Control
     * @param string $customDescription Custom Description for Control
     *
     * @return BlockControl
     */
    protected function addBlockFormControl(string $blockHandle, object $layoutSet, ?string $customName = null, ?string $customDescription = null): BlockControl
    {
        $fc = new BlockControl();
        $bID = BlockType::getByHandle($blockHandle)->getBlockTypeID();
        $fc->setBlockTypeID($bID);
        $formLayoutSet = $fc->addToPageTypeComposerFormLayoutSet($layoutSet);
        if (!empty($customName)) {
            $formLayoutSet->updateFormLayoutSetControlCustomLabel($customName);
        }
        if (!empty($customDescription)) {
            $formLayoutSet->updateFormLayoutSetControlDescription($customDescription);
        }

        return $fc;
    }

    /**
     * Add Block Type Set
     *
     * @param  string                        $handle
     * @param  string                        $name
     * @param  \Concrete\Core\Entity\Package $pkg
     * @return BlockTypeSet
     */
    protected function addBlockTypeSet(string $handle, string $name, \Concrete\Core\Entity\Package $pkg): BlockTypeSet
    {
        return is_object(BlockTypeSet::getByHandle($handle)) ? BlockTypeSet::getByHandle($handle) : BlockTypeSet::add($handle, $name, $pkg);
    }
}
