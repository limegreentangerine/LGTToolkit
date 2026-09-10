<?php

use Concrete\Core\User\User;
use Concrete\Core\File\Set\Set as FileSet;
use Concrete\Core\Attribute\CustomNoValueTextAttributeInterface;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Application\Service\Dashboard                    $dashboard
 * @var Concrete\Core\Form\Service\Form                                $form
 * @var Concrete\Core\Html\Service\Html                                $html
 * @var Concrete\Core\Application\Service\UserInterface                $interface
 * @var Concrete\Core\Validation\CSRF\Token                            $token
 * @var Concrete\Controller\SinglePage\Dashboard\Files\Details         $controller
 * @var Concrete\Core\Localization\Service\Date                        $date
 * @var Concrete\Core\Utility\Service\Number                           $number
 * @var Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface    $resolverManager
 * @var Concrete\Core\Entity\File\Version                              $fileVersion
 * @var Concrete\Core\Permission\Checker                               $filePermissions
 * @var string                                                         $thumbnail
 * @var Concrete\Core\Entity\Attribute\Key\FileKey[]                   $attributeKeys
 * @var Concrete\Core\Entity\Statistics\UsageTracker\FileUsageRecord[] $usageRecords
 * @var Concrete\Core\Entity\File\DownloadStatistics[]                 $recentDownloads
 */

$file = $fileVersion->getFile();
$genericType = $fileVersion->getTypeObject()->getGenericType();
if (isset($view) && $view->controller->getAction() == 'preview_version') { ?>
    <div class="alert alert-info d-flex align-items-center"><div><?php echo t('You are currently previewing file version %s.', $fileVersion->getFileVersionID())?></div>
    <a href="<?php echo URL::to('/dashboard/files', 'details', $file->getFileID())?>" class="btn-sm btn btn-secondary d-flex ms-auto"><?php echo t('Exit Preview')?></a>
    </div>
<?php } ?>

<section>
    <div class="row gx-5">
        <div class="col-lg-6">
            <div class="ccm-file-manager-details-preview-thumbnail">
                <?php if ($fileVersion->canView()) { ?>
                    <a
                        href="<?php echo URL::to('/ccm/system/file/view')?>?fID=<?php echo $file->getFileID()?>"
                        class="dialog-launch"
                        dialog-width="90%"
                        dialog-height="75%"
                    ><?php echo $thumbnail ?></a>
                <?php } else { ?>
                    <?php echo $thumbnail ?>
                <?php } ?>
            </div>
        </div>
        <div class="col-lg-6">
            <?php if (isset($view) && $view->controller->getAction() != 'preview_version') { ?>

                <?php
                if ($filePermissions->canEditFileProperties() || (
                    $filePermissions->canEditFileContents() && (
                        $genericType === \Concrete\Core\File\Type\Type::T_IMAGE
                        || $fileVersion->canEdit()
                    )
                )
                ) {
                    ?>
                <div class="dropdown float-end">
                    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <?php echo t('Edit')?>
                    </button>
                    <ul class="dropdown-menu">
                        <?php
                            if ($filePermissions->canEditFileProperties()) {
                                ?>
                            <li>
                                    <a
                                            data-bs-placement="left"
                                            class="dropdown-item launch-tooltip dialog-launch"
                                            dialog-title="<?php echo t('Attributes') ?>"
                                            dialog-width="850" dialog-height="80%"
                                            title="<?php echo t('Change the name, description, tags and custom attributes of this file.')?>"
                                            href="<?php echo URL::to('/ccm/system/dialogs/file/properties')?>?fID=<?php echo $file->getFileID()?>"
                                    ><?php echo t('Edit Attributes') ?></a>
                            </li>
                            <?php
                            }
                    if ($genericType === \Concrete\Core\File\Type\Type::T_IMAGE
                        && $filePermissions->canEditFileContents()) {
                        // If it's an SVG there will be not thumbnails to edit, so we don't show the thumbnails option. It would also make no sense given the nature of SVG as an image format.
                        if ($fileVersion->getTypeObject()->isSVG()) {
                            $dialogURL = '/ccm/system/file/view?fID=';
                            $dialogLinkLabel = t('View');
                            $dialogLinkTitle = t('View this SVG.');
                            $dialogTitle = t('View');
                        } else {
                            $dialogURL = '/ccm/system/dialogs/file/thumbnails?fID=';
                            $dialogLinkLabel = t('Thumbnails');
                            $dialogLinkTitle = t('Adjust the thumbnails for this image.');
                            $dialogTitle = t('Edit');
                        }
                        ?>
                            <li><a
                                    data-bs-placement="left"
                                    class="dropdown-item launch-tooltip dialog-launch"
                                    dialog-title="<?php echo $dialogTitle ?>"
                                    dialog-width="90%" dialog-height="75%"
                                    title="<?php echo $dialogLinkTitle ?>"
                                    href="<?php echo URL::to($dialogURL . $file->getFileID())?>"
                            ><?php echo $dialogLinkLabel ?></a></li>
                            <?php
                    }
                    if ($fileVersion->canEdit() && $filePermissions->canEditFileContents()) {
                        ?>
                            <li>
                                <a
                                        data-bs-placement="left"
                                        class="dropdown-item launch-tooltip dialog-launch"
                                        dialog-title="<?php echo t('Edit') ?>"
                                        dialog-width="90%" dialog-height="75%"
                                        <?php
                                    if ($genericType === \Concrete\Core\File\Type\Type::T_IMAGE) { ?>
                                            title="<?php echo t('Resize, crop or apply filters to this image.') ?>"
                                        <?php } else { ?>
                                            title="<?php echo t('Edit this file.') ?>"
                                            <?php
                                        }
                        ?>
                                        href="<?php echo URL::to('/ccm/system/file/edit')?>?fID=<?php echo $file->getFileID()?>">
                                        <?php
                        if ($genericType === \Concrete\Core\File\Type\Type::T_IMAGE) { ?>
                                            <?php echo t('Open Image Editor') ?>
                                        <?php } else { ?>
                                            <?php echo t('Edit File Contents') ?>
                                            <?php
                                        }
                        ?>
                                </a>
                            </li>
                            <?php
                    }
                    ?>
                        <?php if ($pkg = \Package::getByHandle('lgt_toolkit') && $genericType === \Concrete\Core\File\Type\Type::T_IMAGE) { ?>
                            <li>
                                <a
                                    data-bs-placement="left"
                                    class="dropdown-item launch-tooltip dialog-launch"
                                    dialog-title="<?php echo t('Focal Point') ?>"
                                    dialog-width="90%" dialog-height="75%"
                                    title="<?php echo t('Set the focal point of this image.') ?>"
                                    href="<?php echo URL::to('/lgt_toolkit/focal_point')?>?fID=<?php echo $file->getFileID()?>"
                                >
                                        <?php echo t('Set Focal Point') ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>


                <?php } ?>
            <?php } ?>

            <h3 class="mb-4"><?php echo t('Attributes')?></h3>
            <dl class="ccm-file-manager-details-attributes">
                <dt><?php echo t('Title') ?></dt>
                <dd>
                    <div><?php echo (string) $fileVersion->getTitle() === '' ? '<i>' . t('No title') . '</i>' : h($fileVersion->getTitle()) ?></div>
                </dd>
                <dt><?php echo t('Description') ?></dt>
                <dd>
                    <div><?php echo (string) $fileVersion->getDescription() === '' ? '<i>' . t('No description') . '</i>' : nl2br(h($fileVersion->getDescription())) ?></div>
                </dd>
                <dt><?php echo t('Tags') ?></dt>
                <dd>
                    <?php
                    $tags = preg_split('/\s*\n\s*/', (string) $fileVersion->getTags(), -1, PREG_SPLIT_NO_EMPTY);
if ($tags === []) {
    ?>
                        <i><?php echo t('No tags') ?></i>
                        <?php
} else {
    ?>
                        <span><?php echo h(implode(', ', $tags)) ?></span>
                        <?php
}
?>
                </dd>
                <dt><?php echo t('Size') ?></dt>
                <dd>
                    <div>
                        <?php
    echo sprintf(
        '%s (%s)',
        $fileVersion->getSize(),
        t2(
            /*i18n: %s is a number */
            '%s byte',
            '%s bytes',
            $fileVersion->getFullSize(),
            $number->format($fileVersion->getFullSize()),
        ),
    );
?>
                    </div>
                </dd>
                <?php
                foreach ($attributeKeys as $attributeKey) {
                    ?>
                    <dt><?php echo $attributeKey->getAttributeKeyDisplayName() ?></dt>
                    <dd>
                        <div>
                            <?php
                            $attributeValue = $fileVersion->getAttributeValueObject($attributeKey);
                    if ($attributeValue === null) {
                        $noValueDisplayHtml = '<i>' . t('None') . '</i>';
                        if (method_exists($attributeKey, 'getController')) {
                            $attributeController = $attributeKey->getController();
                            if ($attributeController instanceof CustomNoValueTextAttributeInterface) {
                                $noValueDisplayHtml = (string) $attributeController->getNoneTextDisplayValue();
                            }
                        }
                        echo $noValueDisplayHtml;
                    } else {
                        echo (string) $attributeValue;
                    }
                    ?>
                        </div>
                    </dd>
                    <?php
                }
?>
            </dl>
        </div>
    </div>
</section>

<hr class="mt-5 mb-4"/>

<section>
    <h3 class="mb-4"><?php echo t('URLs & IDs')?></h3>
    <dl>
        <?php if ($file->hasFileUUID()) { ?>
            <dt><?php echo t('Identifier') ?></dt>
            <dd class="mb-5">
                <input type="text" class="form-control" readonly onclick="this.select()" value="<?php echo h($fileVersion->getFileUUID()) ?>">
                <div class="text-muted mt-2"><i><?php echo t('Use this identifier if you need to work with this file programmatically, or in a REST API operation.') ?></i></div>
            </dd>
        <?php } ?>
        <dt><?php echo t('Direct URL') ?></dt>
        <dd class="mb-5">
            <input type="text" class="form-control" readonly onclick="this.select()" value="<?php echo h($fileVersion->getURL()) ?>">
            <div class="text-muted mt-2"><i><?php echo t('If you need to embed an image directly in HTML, use this URL.') ?></i></div>
        </dd>
        <dt><?php echo t('Tracking URL') ?></dt>
        <dd>
            <input type="text" class="form-control" readonly onclick="this.select()" value="<?php echo h($fileVersion->getDownloadURL()) ?>">
            <div class="text-muted mt-2"><i><?php echo t('By using this URL Concrete will still be able to manage permissions and track statistics on its use.') ?></i></div>
        </dd>
    </dl>
</section>

<hr class="mt-5 mb-4"/>

<section>
    <h3 class="mb-4"><?php echo t('Sets')?></h3>
    <?php if (isset($view) && $view->controller->getAction() != 'preview_version') { ?>
        <a
                class="btn btn-secondary btn-section dialog-launch"
                dialog-title="<?php echo t('Sets') ?>"
                dialog-width="850" dialog-height="600"
                href="<?php echo h($resolverManager->resolve(['/ccm/system/dialogs/file/sets?fID=' . $file->getFileID()])) ?>">
            <?php echo t('Edit')?>
        </a>
    <?php } ?>
    <dl class="ccm-file-manager-details-sets">
        <dt><?php echo t('Sets') ?></dt>
        <dd>
            <?php
            $fileSets = $file->getFileSets();
if ($fileSets === []) {
    ?>
                <i><?php echo t('No file set') ?></i>
                <?php
} else {
    $fileSetNames = array_map(
        function (FileSet $fileSet) {
            return $fileSet->getFileSetDisplayName();
        },
        $fileSets,
    );
    ?>
                <span><?php echo implode(', ', $fileSetNames) ?></span>
                <?php
}
?>
            <div class="text-muted mt-2">
                <i><?php echo t('You can add this file to many sets. Lots of image sliders/galleries use sets to determine what to display.') ?></i>
            </div>
        </dd>
    </dl>
</section>

<hr class="mt-5 mb-4"/>

<section>
    <h3><?php echo t('Statistics') ?></h3>
    <dl class="ccm-file-manager-details-statistics">
        <dt><?php echo t('Date Added') ?></dt>
        <dd>
            <i><?php echo t(/*%1$s is a user name, %2$s is a date/time*/ 'Added by %1$s on %2$s', h($fileVersion->getAuthorName()), h($date->formatPrettyDateTime($fileVersion->getDateAdded(), true))) ?></i>
        </dd>
        <dt><?php echo t('Total Downloads') ?></dt>
        <dd><i><?php echo $number->format($file->getTotalDownloads(), 0) ?></i></dd>
        <dt><?php echo t('Most Recent Downloads') ?></dt>
        <dd>
            <?php
if ($recentDownloads === []) {
    ?><i><?php echo t('No downloads') ?></i><?php
} else {
    ?>
                <table class="table table-bordered">
                    <tbody>
                    <?php
        foreach ($recentDownloads as $recentDownload) {
            ?>
                        <tr>
                            <td>
                                <?php
                    if ($recentDownload->getDownloaderID() === null) {
                        ?><i><?php echo t('Guest') ?></i><?php
                    } else {
                        $downloader = User::getByUserID($recentDownload->getDownloaderID());
                        if ($downloader && $downloader->isRegistered()) {
                            echo h($downloader->getUserName());
                        } else {
                            ?>
                                        <i><?php echo t('Deleted user (ID: %s)', $recentDownload->getDownloaderID()) ?></i><?php
                        }
                    }
            ?>
                            </td>
                            <td><?php echo h($date->formatPrettyDateTime($recentDownload->getDownloadDateTime(), true)) ?></td>
                            <td><?php echo t('Version %s', $recentDownload->getFileVersion()) ?></td>
                        </tr>
                        <?php
        }
    ?>
                    </tbody>
                </table>
                <a
                        class="btn btn-secondary dialog-launch"
                        dialog-title="<?php echo t('Download Statistics') ?>"
                        dialog-width="620" dialog-height="400"
                        href="<?php echo h($resolverManager->resolve(['/ccm/system/dialogs/file/statistics', $file->getFileID()])) ?>"
                ><?php echo t('More') ?></a>
                <?php
}
?>
            <div class="text-muted mt-2">
                <i><?php echo t('If this file is downloaded through the File Block we track it here.') ?></i></div>
        </dd>
        <dt><?php echo t('File Usage') ?></dt>
        <dd>
            <?php
if ($usageRecords === []) {
    ?>
                <i><?php echo t("It seems that this file isn't used anywhere.") ?></i>
                <?php
} else {
    ?>
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th><?php echo t('Page ID') ?></th>
                        <th><?php echo t('Version') ?></th>
                        <th><?php echo t('Handle') ?></th>
                        <th><?php echo t('Location') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
        foreach ($usageRecords as $usageRecord) {
            $page = \Page::getByID($usageRecord->getCollectionId(), $usageRecord->getCollectionVersionId());
            if (!$page || $page->isError()) {
                $page = null;
            }
            ?>
                        <tr>
                            <td><?php echo $usageRecord->getCollectionId() ?></td>
                            <td><?php echo $usageRecord->getCollectionVersionId() ?></strong></td>
                            <td><?php echo $page === null ? '<i>' . t('n/a') . '</i>' : '<strong>' . h($page->getCollectionHandle()) . '</strong>' ?></td>
                            <td>
                                <?php
                    if ($page === null) {
                        ?>
                                    <i><?php echo t('n/a') ?></i>
                                    <?php
                    } else {
                        $pagePath = '/' . ltrim((string) $page->getCollectionPath(), '/');
                        ?>
                                    <a href="<?php echo $resolverManager->resolve([$page]) ?>"><strong><?php echo h($pagePath) ?></strong></a>
                                    <?php
                    }
            ?>
                            </td>
                        </tr>
                        <?php
        }
    ?>
                    </tbody>
                </table>
                <?php
}
?>
        </dd>
    </dl>
</section>

<hr class="mt-5 mb-4"/>

<section>
    <?php if (isset($view) && $view->controller->getAction() != 'preview_version') { ?>
        <?php
        if ($filePermissions->canEditFilePermissions()) {
            ?>
            <a
                    class="btn btn-secondary float-end dialog-launch"
                    dialog-title="<?php echo t('Storage Location') ?>"
                    dialog-width="500" dialog-height="400"
                    href="<?php echo h($resolverManager->resolve(['/ccm/system/dialogs/file/bulk/storage?fID[]=' . $file->getFileID()])) ?>"
            ><?php echo t('Edit') ?></a>
            <?php
        }
        ?>
    <?php } ?>
    <h3><?php echo t('Storage') ?></h3>
    <dl class="ccm-file-manager-details-storage">
        <dt><?php echo t('Storage Locations') ?></dt>
        <dd>
            <?php echo $file->getFileStorageLocationObject()->getDisplayName() ?>
            <div class="text-muted"><?php echo t('You can use S3 or other cloud storage solutions to distribute your content.') ?></div>
        </dd>
    </dl>
</section>

<script>
    $(document).ready(function () {
        ConcreteEvent.subscribe('FileManagerReplaceFileComplete FileManagerBulkFileStorageComplete', function (e, data) {
            location.reload();
        });
    });
</script>
