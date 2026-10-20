<?php

namespace LgtToolkit\Search\Result;

use ClassKit\Search\Result as SearchResult;

class Redirections extends SearchResult
{
    public function getItemDetails($result)
    {
        return new Item\Redirections($this, $this->listColumns, $result);
    }
}
