<?php

namespace LgtToolkit\Command\Task;

use Concrete\Core\Foundation\Command\Command;

class RetrySupportTicketsCommand extends Command
{
    public function __construct(public readonly bool $dryRun = false) {}
}
