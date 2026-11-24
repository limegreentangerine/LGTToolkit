<?php

namespace LgtToolkit\Support\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Command\Task\TaskService;
use Concrete\Core\Entity\Command\ScheduledTask;

/**
 * Queued tickets are only sent by the lgt_support_retry task when it is scheduled under
 * Dashboard > System & Settings > Automation; this reports whether that schedule exists.
 */
class RetrySchedule
{
    public const TASK_HANDLE = 'lgt_support_retry';

    public function __construct(
        private readonly TaskService $tasks,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Cron expressions of the task's schedules (empty when it isn't scheduled).
     *
     * @return list<string>
     */
    public function cronExpressions(): array
    {
        $task = $this->tasks->getByHandle(self::TASK_HANDLE);
        if ($task === null) {
            return [];
        }

        return array_map(
            static fn(ScheduledTask $s) => (string) $s->getCronExpression(),
            $this->em->getRepository(ScheduledTask::class)->findBy(['task' => $task]),
        );
    }

    public function isScheduled(): bool
    {
        return $this->cronExpressions() !== [];
    }
}
