<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Carbon\Carbon;
use Spiral\Console\Command;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\JobRegistryInterface;
use Symfony\Component\Console\Helper\Table;

final class ScheduleListCommand extends Command
{
    protected const NAME = 'schedule:list';
    protected const DESCRIPTION = 'List the scheduled jobs';

    public function perform(
        JobRegistryInterface $registry,
        SchedulerConfig $config,
    ): int {
        $jobs = $registry->getJobs();

        if (\count($jobs) === 0) {
            $this->writeln('No scheduled jobs registered.');

            return self::SUCCESS;
        }

        $date = Carbon::now($config->getTimezone());

        $table = new Table($this->output);
        $table->setHeaders([
            'Command',
            'Interval',
            'Description',
            'Next Due',
        ]);

        foreach ($registry->getJobs() as $job) {
            $table->addRow([
                $job->getName(),
                $job->getExpression(),
                $job->getDescription(),
                $job->getNextRunDate($date)->format('Y-m-d H:i:s P'),
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
