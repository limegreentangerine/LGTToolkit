<?php

namespace LgtToolkit\File\FocalPoint;

use Exception;
use Concrete\Core\Entity\File\File;
use Concrete\Core\Entity\File\Version;
use LgtToolkit\Entity\File\ImageFocalPoint;

class FocalPointService
{
    public function getFocalPoint(File|Version $file, $asObject = false)
    {
        $fID = false;
        if ($file instanceof Version) {
            $fID = $file->getFile()->getFileID();
        } else {
            $fID = $file->getFileID();
        }

        if ($fID === false || $fID === null) {
            throw new Exception(t('File ID not found on %s', get_class($file)), 404);
        }

        $focalPoint = ImageFocalPoint::getByColumnAndValue('fID', $fID);

        if ($focalPoint) {
            return ($asObject) ? $focalPoint : $focalPoint->getFocalPoint();
        }

        return false;
    }
}
