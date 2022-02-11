<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use Spiral\Console\Command;
use Spiral\Core\Container;
use Spiral\Scheduler\Job\CallbackJob;
use Spiral\Scheduler\Job\CommandJob;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\Mutex\JobMutexInterface;

final class Schedule
{
    private const DEFAULT_EXPRESSION = '* * * * *';

    /** @var array<Job> */
    private array $jobs = [];

    public function __construct(
        private Container $container,
        private CommandRunner $commandRunner,
        private JobMutexInterface $jobMutex,
        private \DateTimeZone $timezone
    ) {
    }

    /**
     * Get all of the jobs on the schedule that are due.
     *
     * @return iterable<int,Job>
     */
    public function dueJobs(): iterable
    {
        $date = Carbon::now()->setTimezone($this->timezone);

        foreach ($this->jobs as $job) {
            if (! $job->isDue($date)) {
                continue;
            }

            yield $job;
        }
    }

    /**
     * Get all of the jobs on the schedule.
     *
     * @return array<int,Job>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Add a new console command to the schedule.
     */
    public function command(string $commandName, array $parameters = [], string $description = null): CommandJob
    {
        if (class_exists($commandName)) {
            /** @var Command $command */
            $command = $this->container->make($commandName);

            if ($command instanceof Command) {
                $commandName = $command->getName();
                $description = $command->getDescription();
            }
        }

        return $this->exec(
            $this->commandRunner->formatCommandString($commandName),
            $parameters,
            $description
        );
    }

    /**
     * Add a new command job to the schedule.
     */
    public function exec(string $command, array $parameters = [], ?string $description = null): CommandJob
    {
        if (count($parameters)) {
            $command .= ' '.CommandUtils::compileParameters($parameters);
        }

        $job = new CommandJob(
            commandBuilder: new CommandBuilder($this->commandRunner),
            mutex: $this->jobMutex,
            expression: $this->createCronExpression(),
            command: $command
        );

        $job->description($description);

        return $this->registerJob($job);
    }

    public function call(string $description, Closure $callback, array $parameters = []): CallbackJob
    {
        return $this->registerJob(
            new CallbackJob(
                mutex: $this->jobMutex,
                expression: $this->createCronExpression(),
                description: $description,
                callback: $callback,
                parameters: $parameters
            )
        );
    }

    public function registerJob(Job $job): Job
    {
        $this->jobs[] = $job;

        return $job;
    }

    private function createCronExpression(): CronExpression
    {
        return new CronExpression(static::DEFAULT_EXPRESSION);
    }
}
