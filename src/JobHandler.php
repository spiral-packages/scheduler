<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Spiral\Scheduler\Event\JobFailed;
use Spiral\Scheduler\Event\JobFinished;
use Spiral\Scheduler\Event\JobStarting;
use Spiral\Scheduler\Job\Job;
use Spiral\Snapshots\SnapshotterInterface;

final class JobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ?SnapshotterInterface $snapshotter = null,
        private readonly ?EventDispatcherInterface $dispatcher = null
    ) {
    }

    public function handle(Job $job): void
    {
        $this->dispatcher?->dispatch(new JobStarting($job));

        $start = \microtime(true);

        try {
            $job->run($this->container);

            $this->dispatcher?->dispatch(new JobFinished($job, \round(\microtime(true) - $start, 2)));
        } catch (\Throwable $e) {
            $this->dispatcher?->dispatch(new JobFailed($job, $e));
            $this->snapshotter?->register($e);
        }
    }
}
