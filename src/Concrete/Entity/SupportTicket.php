<?php

namespace Concrete\Package\LgtToolkit\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LgtToolkit\Support\TicketLog;
use LgtToolkit\Support\TicketCategory;
use ClassKit\Entity\Core\UpdatedGuidEntity;

/**
 * Every ticket submitted through the site, for the super user's ticket list; purged after 12 months.
 * Reads and writes go through LgtToolkit\Support\TicketLog (DBAL), so this class defines the schema.
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 * @ORM\Table(
 *      name="LgtSupportTickets",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="localReference", columns={"localReference"})}
 * )
 */
class SupportTicket extends UpdatedGuidEntity
{
    /**
     * @ORM\Column(type="string", length=20)
     */
    protected string $localReference = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected string $category = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected string $email = '';

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(type="integer", options={"unsigned": true}, nullable=true)
     */
    protected ?int $uID = null;

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
     * @ORM\Column(type="string", length=12)
     */
    protected string $status = TicketLog::STATUS_QUEUED;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $portalTicket = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $portalReference = null;

    /**
     * Link to the ticket in the portal; only stored when on the portal endpoint's own host.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $portalUrl = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $deliveredAt = null;

    /**
     * Redacted, at most 2000 characters.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $lastError = null;

    public function getLocalReference(): string
    {
        return $this->localReference;
    }

    public function getPortalTicket(): ?int
    {
        return $this->portalTicket;
    }

    public function getPortalReference(): ?string
    {
        return $this->portalReference;
    }

    public function getPortalUrl(): ?string
    {
        return $this->portalUrl;
    }

    /**
     * The portal reference once delivered, otherwise the local one.
     */
    public function getDisplayReference(): string
    {
        return $this->portalReference ?: $this->localReference;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            TicketLog::STATUS_DELIVERED => t('Delivered'),
            TicketLog::STATUS_QUEUED => t('Queued'),
            default => t('Failed'),
        };
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Falls back to the stored value for categories that no longer exist.
     */
    public function getCategoryLabel(): string
    {
        return TicketCategory::tryFrom($this->category)?->label() ?? $this->category;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSubmitter(): string
    {
        return $this->name ? $this->name . ' <' . $this->email . '>' : $this->email;
    }

    public function getUserID(): ?int
    {
        return $this->uID;
    }

    public function getPageUrl(): ?string
    {
        return $this->pageUrl;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
