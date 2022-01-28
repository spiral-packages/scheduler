<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Spiral\Console\Command;
use Spiral\Scheduler\Event\Event;
use Spiral\Scheduler\Schedule;
use Spiral\Snapshots\SnapshotterInterface;

final class ScheduleRunCommand extends Command
{
    protected const NAME = 'schedule:run';
    protected const DESCRIPTION = 'Run the scheduled commands';

    private SnapshotterInterface $snapshotter;

    public function perform(Schedule $schedule, SnapshotterInterface $snapshotter): int
    {
        $this->snapshotter = $snapshotter;

        $eventsRan = false;

        foreach ($schedule->dueEvents() as $event) {
            if (! $event->filtersPass($this->container)) {
                continue;
            }

            $this->runEvent($event);
            $eventsRan = true;
        }

        if (! $eventsRan) {
            $this->writeln('No scheduled commands are ready to run.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runEvent(Event $event): void
    {
        try {
            $this->writeln(
                sprintf(
                    '<info>[%s] Running scheduled command:</info> %s',
                    date('c'),
                    $event->getDescription() ?? $event->getSystemDescription()
                )
            );

            $event->run($this->container);
        } catch (\Throwable $e) {
            $this->snapshotter->register($e);
        }
    }
}
