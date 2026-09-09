<?php

namespace LgtToolkit\Providers\FocalPoint;

use Exception;
use Concrete\Core\Entity\File\File;
use Concrete\Core\Entity\File\Version;
use LgtToolkit\Entity\File\ImageFocalPoint;

/**
 * Provides file focal-point lookup helpers.
 */
class FocalPointService
{
    /**
     * Retrieves the focal point for a file or file version.
     *
     * @param File|Version $file The file or file version to inspect.
     * @param bool $asObject Whether to return the focal-point entity instead of its coordinates.
     *
     * @return mixed The focal point value or false when no focal point exists.
     */
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
