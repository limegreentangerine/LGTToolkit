<?php

namespace LgtToolkit\Search\Result\Item;

use Concrete\Core\Search\Result\Item;
use ClassKit\Search\Result\Item\ItemTrait;
use Concrete\Core\Support\Facade\Url as URL;

class SupportTickets extends Item
{
    use ItemTrait;

    public function getViewUrl()
    {
        return URL::to('/dashboard/support/tickets/detail', $this->entity->getID());
    }

    public function getStatus(): string
    {
        return $this->entity->getStatus();
    }
}
