<?php

namespace Concrete\Package\LgtToolkit\Entity;

use Doctrine\ORM\Mapping as ORM;
use ClassKit\Entity\Core\GuidEntity;

/**
 * Raw bytes of a queued ticket's attachment. Deleted with its item (FK cascade); never written to the File Manager.
 *
 * @ORM\Entity
 * @ORM\Table(name="LgtSupportTicketQueueAttachments")
 */
class SupportTicketQueueAttachment extends GuidEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="SupportTicketQueueItem")
     * @ORM\JoinColumn(name="queueItemID", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected SupportTicketQueueItem $item;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected string $filename = '';

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected string $mime = '';

    /**
     * @ORM\Column(type="integer", options={"unsigned": true})
     */
    protected int $size = 0;

    /**
     * MEDIUMBLOB (16 MB): a plain BLOB holds only 64 KB.
     *
     * @ORM\Column(type="blob", length=16777215)
     */
    protected $content;
}
