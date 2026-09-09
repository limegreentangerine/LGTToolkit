<?php

namespace LgtToolkit\Events;

use Core;
use Concrete\Core\File\Event\DeleteFile;
use Doctrine\ORM\EntityManagerInterface;
use LgtToolkit\Entity\File\ImageFocalPoint;

/**
 * Removes stored focal point metadata when a file is deleted.
 */
class File
{
    /**
     * Deletes any focal-point data associated with the removed file.
     *
     * @param DeleteFile $event The file deletion event.
     */
    public static function removeFocalPoint(DeleteFile $event)
    {
        $file = $event->getFileObject();
        $focalPoint = ImageFocalPoint::getByColumnAndValue('fID', $file->getFileID());

        if (is_object($focalPoint)) {
            $em = Core::make(EntityManagerInterface::class);
            $em->remove($focalPoint);
            $em->flush($focalPoint);
        }
    }
}
