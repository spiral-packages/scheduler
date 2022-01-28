<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Commands;

use Carbon\Carbon;
use Spiral\Console\Command;
use Spiral\Scheduler\CommandRunner;
use Spiral\Scheduler\Schedule;
use Symfony\Component\Process\Process;

final class ScheduleWorkCommand extends Command
{
    protected const NAME = 'schedule:work';
    protected const DESCRIPTION = 'Start the schedule worker';

    public function perform(Schedule $schedule, CommandRunner $runner): int
    {
        $this->writeln('Schedule worker started successfully.');

        [$lastExecutionStartedAt, $keyOfLastExecutionWithOutput, $executions] = [null, null, []];

        while (true) {
            usleep(100 * 1000);

            if (Carbon::now()->second === 0 &&
                ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $executions[] = $execution = new Process([$runner->phpBinary(), $runner->spiralBinary(), 'schedule:run']
                );
                $execution->start();
                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($executions as $key => $execution) {
                $output = trim($execution->getIncrementalOutput()).
                    trim($execution->getIncrementalErrorOutput());

                if (! empty($output)) {
                    if ($key !== $keyOfLastExecutionWithOutput) {
                        $this->writeln(PHP_EOL.'['.date('c').'] Execution #'.($key + 1).' output:');

                        $keyOfLastExecutionWithOutput = $key;
                    }

                    $this->output->writeln($output);
                }

                if (! $execution->isRunning()) {
                    unset($executions[$key]);
                }
            }
        }

        return self::SUCCESS;
    }
}
