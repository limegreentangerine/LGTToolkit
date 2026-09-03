<?php

namespace LgtToolkit\Entity\File;

use Doctrine\ORM\Mapping as ORM;
use LgtToolkit\Entity\Core\BaseEntity;
use Concrete\Core\Entity\File\File as FileEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="ImageFocalPoint"
 * )
 */
class ImageFocalPoint extends BaseEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Concrete\Core\Entity\File\File")
     * @ORM\JoinColumn(name="fID", referencedColumnName="fID")
     */
    protected FileEntity $fID;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $x;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $y;

    /**
     * Get the value of fID
     */
    public function getFile(): FileEntity
    {
        return $this->fID;
    }

    /**
     * Set the value of fID
     *
     * @return self
     */
    public function setFile(FileEntity $fID): self
    {
        $this->fID = $fID;

        return $this;
    }

    /**
     * Get the value of x
     */
    public function getX($asPercentage = false): string
    {
        return ($asPercentage) ? $this->x . '%' : $this->x;
    }

    /**
     * Set the value of x
     *
     * @return self
     */
    public function setX(string $x): self
    {
        $this->x = $x;

        return $this;
    }

    /**
     * Get the value of y
     */
    public function getY($asPercentage = false): string
    {
        return ($asPercentage) ? $this->y . '%' : $this->y;
    }

    /**
     * Set the value of y
     *
     * @return self
     */
    public function setY(string $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function getFocalPoint(): string
    {
        return sprintf('%s %s', $this->getX(true), $this->getY(true));
    }
}
