<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Carbon\Carbon;
use Spiral\Scheduler\Config\SchedulerConfig;

class EveryMinuteCommandRunner implements PeriodicCommandRunnerInterface
{
    private ?Carbon $lastExecutionStartedAt = null;
    private array $executions = [];

    public function __construct(
        private readonly ProcessFactory $processFactory,
        private readonly CommandRunner $runner,
        private readonly SchedulerConfig $config
    ) {
    }

    public function run(string $command, \Closure $onSuccess = null, \Closure $onError = null): void
    {
        while (true) {
            $this->waitMinute();

            if ($this->shouldProcessBeExecuted()) {
                $this->executions[] = $execution = $this->processFactory
                    ->createFromShellCommandline(
                        $this->runner->formatCommandString($command)
                    );

                $execution->start();
                $this->lastExecutionStartedAt = $this->now()->startOfMinute();
            }

            foreach ($this->executions as $key => $execution) {
                $output = \trim($execution->getIncrementalOutput());
                $errorOutput = \trim($execution->getIncrementalErrorOutput());

                if (! empty($output)) {
                    $onSuccess($output);
                }

                if (! empty($errorOutput)) {
                    $onError($errorOutput);
                }

                if (! $execution->isRunning()) {
                    unset($this->executions[$key]);
                }
            }
        }
    }

    private function now(): Carbon
    {
        return Carbon::now($this->config->getTimezone());
    }

    private function shouldProcessBeExecuted(): bool
    {
        return $this->now()->second === 0
            && ! $this->now()->startOfMinute()->equalTo($this->lastExecutionStartedAt);
    }

    private function waitMinute(): void
    {
        \usleep(100 * 1000);
    }
}
