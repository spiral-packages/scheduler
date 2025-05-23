<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Cron\CronExpression;
use Spiral\Console\Command;
use Spiral\Core\FactoryInterface;
use Spiral\Scheduler\Job\CallbackJob;
use Spiral\Scheduler\Job\CommandJob;
use Spiral\Scheduler\Mutex\JobMutexInterface;

final class Schedule
{
    private const DEFAULT_EXPRESSION = '* * * * *';

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly ProcessFactory $processFactory,
        private readonly JobRegistryInterface $jobs,
        private readonly CommandRunner $commandRunner,
        private readonly JobMutexInterface $jobMutex,
    ) {}

    /**
     * Add a new console command to the schedule.
     */
    public function command(string $commandName, array $parameters = [], ?string $description = null, ?CronExpression $expression = null): CommandJob
    {
        if (\class_exists($commandName)) {
            /** @var Command $command */
            $command = $this->factory->make($commandName);

            if ($command instanceof Command) {
                $commandName = $command->getName();
                $description = $command->getDescription();
            }
        }

        return $this->exec(
            $this->commandRunner->formatCommandString($commandName),
            $parameters,
            $description,
            $expression,
        );
    }

    /**
     * Add a new command job to the schedule.
     */
    public function exec(string $command, array $parameters = [], ?string $description = null, ?CronExpression $expression = null): CommandJob
    {
        if (\count($parameters)) {
            $command .= ' ' . CommandUtils::compileParameters($parameters);
        }

        $job = new CommandJob(
            commandBuilder: new CommandBuilder($this->commandRunner),
            processFactory: $this->processFactory,
            mutex: $this->jobMutex,
            expression: $expression ?? $this->createDefaultCronExpression(),
            command: $command,
        );

        $job->description($description);
        $this->jobs->register($job);

        return $job;
    }

    public function call(string $description, \Closure $callback, array $parameters = [], ?CronExpression $expression = null): CallbackJob
    {
        $job = new CallbackJob(
            mutex: $this->jobMutex,
            expression: $expression ?? $this->createDefaultCronExpression(),
            description: $description,
            callback: $callback,
            parameters: $parameters,
        );

        $this->jobs->register($job);

        return $job;
    }

    private function createDefaultCronExpression(): CronExpression
    {
        return new CronExpression(self::DEFAULT_EXPRESSION);
    }
}
