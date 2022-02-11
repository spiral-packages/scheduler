<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Spiral\Console\Command;
use Spiral\Scheduler\Schedule;
use Symfony\Component\Console\Helper\Table;

final class ScheduleListCommand extends Command
{
    protected const NAME = 'schedule:list';
    protected const DESCRIPTION = 'List the scheduled jobs';

    public function perform(Schedule $schedule): int
    {
        $date = \Carbon\Carbon::now();

        $table = new Table($this->output);
        $table->setHeaders([
            'Command',
            'Interval',
            'Description',
            'Next Due',
        ]);

        foreach ($schedule->getJobs() as $event) {
            $table->addRow([
                $event->getName(),
                $event->getExpression(),
                $event->getDescription(),
                $event->getNextRunDate($date)->format('Y-m-d H:i:s P')
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
