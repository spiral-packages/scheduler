<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Carbon\Carbon;
use Closure;
use Spiral\Console\Command;
use Spiral\Core\Container;
use Spiral\Scheduler\Event\CallbackEvent;
use Spiral\Scheduler\Event\CommandEvent;
use Spiral\Scheduler\Event\Event;
use Spiral\Scheduler\Mutex\EventMutexInterface;

final class Schedule
{
    /** @var array<Event> */
    private array $events = [];

    public function __construct(
        private Container $container,
        private CommandRunner $commandRunner,
        private CommandBuilder $commandBuilder,
        private EventMutexInterface $eventMutex,
        private ?\DateTimeZone $timezone = null
    ) {
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @return iterable<int,Event>
     */
    public function dueEvents(): iterable
    {
        $date = Carbon::now()->setTimezone($this->timezone);

        foreach ($this->events as $event) {
            if (! $event->isDue($date)) {
                continue;
            }

            yield $event;
        }
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return array<int,Event>
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Add a new console command to the schedule.
     */
    public function command(string $commandName, array $parameters = []): CommandEvent
    {
        $description = null;
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
     * Add a new command event to the schedule.
     */
    public function exec(string $command, array $parameters = [], ?string $description = null): CommandEvent
    {
        if (count($parameters)) {
            $command .= ' '.CommandUtils::compileParameters($parameters);
        }

        $this->events[] = $event = new CommandEvent(
            commandBuilder: $this->commandBuilder,
            mutex: $this->eventMutex,
            command: $command
        );

        $event->description($description);

        return $event;
    }

    public function call(string $description, Closure $callback, array $parameters = []): CallbackEvent
    {
        $this->events[] = $event = new CallbackEvent(
            $this->eventMutex, $description, $callback, $parameters
        );

        return $event;
    }
}
