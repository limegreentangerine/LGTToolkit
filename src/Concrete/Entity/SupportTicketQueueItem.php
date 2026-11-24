<?php

namespace Concrete\Package\LgtToolkit\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LgtToolkit\Support\Queue\QueueService;
use ClassKit\Entity\Core\UpdatedGuidEntity;

/**
 * A support ticket waiting to be (re)sent to the helpdesk portal. Rows exist only while delivery is failing;
 * they are deleted once the portal returns 201. Reads and writes go through LgtToolkit\Support\Queue\QueueService
 * (DBAL), so this class mainly defines the schema.
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 * @ORM\Table(
 *      name="LgtSupportTicketQueue",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="localReference", columns={"localReference"})},
 *      indexes={@ORM\Index(name="status_next", columns={"status", "nextAttemptAt"})}
 * )
 */
class SupportTicketQueueItem extends UpdatedGuidEntity
{
    /**
     * @ORM\Column(type="string", length=20)
     */
    protected string $localReference = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected string $email = '';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(type="string", length=200)
     */
    protected string $subject = '';

    /**
     * MEDIUMTEXT: 20,000 multibyte characters can exceed TEXT's 64 KB.
     *
     * @ORM\Column(type="text", length=16777215)
     */
    protected string $message = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $pageUrl = null;

    /**
     * @ORM\Column(type="integer", options={"unsigned": true}, nullable=true)
     */
    protected ?int $uID = null;

    /**
     * @ORM\Column(type="string", length=12)
     */
    protected string $status = QueueService::STATUS_PENDING;

    /**
     * @ORM\Column(type="smallint")
     */
    protected int $attempts = 0;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    protected DateTimeImmutable $nextAttemptAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $lastAttemptAt = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $lockedUntil = null;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected ?int $lastStatusCode = null;

    /**
     * Redacted, at most 2000 characters.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $lastError = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $possibleDuplicate = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $configError = false;

    /**
     * Set on 201 before the row is deleted, to aid debugging if the delete fails.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $portalTicket = null;
}
