<?php

namespace LgtToolkit\Command\Task\Controller;

use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Command\Task\Input\InputInterface;
use LgtToolkit\Command\Task\RetrySupportTicketsCommand;
use Concrete\Core\Command\Task\Input\Definition\Definition;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Command\Task\Input\Definition\BooleanField;
use Concrete\Core\Command\Task\Runner\{ProcessTaskRunner, TaskRunnerInterface};

class RetrySupportTicketsController extends AbstractController
{
    public function getName(): string
    {
        return t('Send queued support tickets');
    }

    public function getDescription(): string
    {
        return t('Retries support tickets that could not be delivered to the Limegreentangerine helpdesk. Schedule it to run every 5 minutes; queued tickets are only sent by this task.');
    }

    public function getInputDefinition(): ?Definition
    {
        $definition = new Definition();
        $definition->addField(new BooleanField('dry-run', t('Dry run'), t('List the due tickets without sending them.')));

        return $definition;
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $dryRun = $input->hasField('dry-run') && (bool) $input->getField('dry-run')->getValue();

        return new ProcessTaskRunner(
            $task,
            new RetrySupportTicketsCommand($dryRun),
            $input,
            t('Sending queued support tickets...'),
        );
    }
}
