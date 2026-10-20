<?php

namespace LgtToolkit\Search\Result\Item;

use Concrete\Core\Search\Result\Item;
use ClassKit\Search\Result\Item\ItemTrait;
use Concrete\Core\Support\Facade\Url as URL;
use LgtToolkit\Redirections\RedirectionService;

class Redirections extends Item
{
    use ItemTrait;

    public function getViewUrl()
    {
        return URL::to('/dashboard/lgt_toolkit/redirections/edit', $this->entity->getID());
    }

    /**
     * The live destination page, or null when it has been deleted or unpublished.
     */
    public function getDestination(): mixed
    {
        return app(RedirectionService::class)->resolveDestination($this->entity);
    }
}
