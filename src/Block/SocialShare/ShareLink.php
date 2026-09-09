<?php

namespace LgtToolkit\Block\SocialShare;

use LgtToolkit\Block\SocialShare\Service as SocialNetwork;
use Concrete\Core\Sharing\SocialNetwork\Service as ConcreteSocialService;

class ShareLink
{
    protected int $id;
    protected int $bID;
    protected string $serviceHandle;

    /**
     * Get the value of id
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return self
     */
    public function setID(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of bID
     */
    public function getBID(): int
    {
        return $this->bID;
    }

    /**
     * Set the value of bID
     *
     * @return self
     */
    public function setBID(int $bID): self
    {
        $this->bID = $bID;

        return $this;
    }

    /**
     * Get the value of serviceHandle
     */
    public function getServiceHandle(): string
    {
        return $this->serviceHandle;
    }

    /**
     * Set the value of serviceHandle
     *
     * @return self
     */
    public function setServiceHandle(string $serviceHandle): self
    {
        $this->serviceHandle = $serviceHandle;

        return $this;
    }

    public function getSocialNetwork()
    {
        $service = ConcreteSocialService::getByHandle($this->serviceHandle);
        $network = new SocialNetwork($service);

        if ($network instanceof SocialNetwork) {
            return $network;
        }

        return false;
    }
}
