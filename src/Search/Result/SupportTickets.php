<?php

namespace LgtToolkit\Search\Result;

use ClassKit\Search\Result as SearchResult;

class SupportTickets extends SearchResult
{
    public function getItemDetails($result)
    {
        return new Item\SupportTickets($this, $this->listColumns, $result);
    }
}
