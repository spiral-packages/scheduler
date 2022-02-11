<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Job;

use Cron\CronExpression;
use Spiral\Core\Container;
use Spiral\Scheduler\CommandBuilder;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Symfony\Component\Process\Process;

final class CommandJob extends Job
{
    public function __construct(
        private CommandBuilder $commandBuilder,
        JobMutexInterface $mutex,
        CronExpression $expression,
        private string $command
    ) {
        parent::__construct($mutex, $expression);
    }

    /**
     * Get the command.
     */
    public function getName(): string
    {
        return $this->command;
    }

    public function run(Container $container): void
    {
        if ($this->withoutOverlapping && ! $this->mutex->create($this->getId(), $this->getExpiresAt())) {
            return;
        }

        $this->runInBackground
            ? $this->runCommandInBackground($container)
            : $this->runCommandInForeground($container);
    }

    /**
     * Run the command in the foreground.
     */
    private function runCommandInForeground(Container $container): void
    {
        try {
            $this->callBeforeCallbacks($container);

            $this->exitCode = Process::fromShellCommandline(
                command: $this->buildCommand(),
                cwd: directory('root'),
                timeout: null
            )->run();

            $this->callAfterCallbacks($container);
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * Run the command in the background.
     */
    private function runCommandInBackground(Container $container): void
    {
        try {
            Process::fromShellCommandline(
                command: $this->buildCommand(),
                cwd: directory('root'),
                timeout: null
            )->run();
        } catch (\Throwable $exception) {
            throw $exception;
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * Get the id for the scheduled command.
     */
    public function getId(): string
    {
        return 'schedule-'.sha1($this->getExpression().$this->command);
    }

    public function getSystemDescription(): string
    {
        return $this->buildCommand();
    }

    private function buildCommand(): string
    {
        if ($this->runInBackground) {
            return $this->commandBuilder->buildBackgroundCommand(
                command: $this->command,
                id: $this->getId(),
                appendOutput: $this->shouldAppendOutput,
                output: $this->output,
                user: $this->user
            );
        }

        return $this->commandBuilder->buildForegroundCommand(
            command: $this->command,
            appendOutput: $this->shouldAppendOutput,
            output: $this->output,
            user: $this->user
        );
    }
}
