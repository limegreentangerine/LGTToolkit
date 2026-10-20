<?php

namespace Concrete\Package\LgtToolkit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\UpdatedGuidEntity;

/**
 * A declared redirection from an old URL path to a page.
 *
 * The source path is also stored as a non-canonical PagePath on the destination
 * page (ppID); this table holds the HTTP status code that core cannot express.
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 * @ORM\Table(
 *      name="LgtRedirections",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="sourcePath", columns={"sourcePath"})},
 *      indexes={@ORM\Index(name="cID", columns={"cID"})}
 * )
 */
class Redirection extends UpdatedGuidEntity
{
    public const DEFAULT_STATUS = 302;

    public const STATUS_CODES = [301, 302];

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $sourcePath = '';

    /**
     * @ORM\Column(type="integer", options={"unsigned": true}, nullable=false)
     */
    protected int $cID = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned": true}, nullable=true)
     */
    protected ?int $ppID = null;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected int $statusCode = self::DEFAULT_STATUS;

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function setSourcePath(string $sourcePath): self
    {
        $this->sourcePath = $sourcePath;

        return $this;
    }

    public function getPageID(): int
    {
        return $this->cID;
    }

    public function setPageID(int $cID): self
    {
        $this->cID = $cID;

        return $this;
    }

    public function getPagePathID(): ?int
    {
        return $this->ppID;
    }

    public function setPagePathID(?int $ppID): self
    {
        $this->ppID = $ppID;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
