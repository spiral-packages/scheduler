<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Job;

use Butschster\CronExpression\Parts\DateTime;
use Butschster\CronExpression\PartValueInterface;
use Butschster\CronExpression\Traits\Days;
use Butschster\CronExpression\Traits\Hours;
use Butschster\CronExpression\Traits\Minutes;
use Butschster\CronExpression\Traits\Months;
use Butschster\CronExpression\Traits\Weeks;
use Butschster\CronExpression\Traits\Years;
use Cron\CronExpression;
use Psr\Container\ContainerInterface;
use Spiral\Core\InvokerInterface;
use Spiral\Scheduler\Mutex\JobMutexInterface;

abstract class Job
{
    use Minutes;
    use Hours;
    use Days;
    use Weeks;
    use Months;
    use Years;

    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const JAN = 1;
    public const FEB = 2;
    public const MAR = 3;
    public const APR = 4;
    public const MAY = 5;
    public const JUN = 6;
    public const JUL = 7;
    public const AUG = 8;
    public const SEP = 9;
    public const OCT = 10;
    public const NOV = 11;
    public const DEC = 12;

    /**
     * The location that output should be sent to.
     */
    protected string $output = '/dev/null';

    /**
     * The user the command should run as.
     */
    protected ?string $user = null;

    /**
     * Indicates if the command should run in the background.
     */
    protected bool $runInBackground = false;

    /**
     * Indicates if the command should not overlap itself.
     */
    protected bool $withoutOverlapping = false;

    /**
     * Indicates whether output should be appended.
     */
    protected bool $shouldAppendOutput = false;

    /**
     * The amount of minutes the mutex should be valid.
     * Default: 24h
     */
    protected int $expiresAt = 1440;

    /**
     * The human-readable description of the job.
     */
    protected ?string $description = null;

    /**
     * The exit status code of the command.
     */
    protected ?int $exitCode = null;

    /**
     * The array of reject callbacks.
     */
    private array $rejects = [];

    /**
     * The array of filter callbacks.
     */
    private array $filters = [];

    /**
     * The array of callbacks to be run before the job is started.
     */
    private array $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the job is finished.
     */
    private array $afterCallbacks = [];

    public function __construct(
        protected readonly JobMutexInterface $mutex,
        protected CronExpression $expression,
    ) {}

    /**
     * Set the human-friendly description of the job.
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Run the command from given user.
     */
    public function runAs(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the Cron expression for the job.
     */
    public function getExpression(): string
    {
        return (string) $this->expression;
    }

    /**
     * Get the amount of minutes the mutex should be valid.
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Get the summary of the job for display.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Determine the next due date for an job.
     * @throws \Exception
     */
    public function getNextRunDate(\DateTimeInterface $date, int $nth = 0, bool $allowCurrentDate = false): \DateTime
    {
        return $this->expression->getNextRunDate($date, $nth, $allowCurrentDate);
    }

    /**
     * Do not allow the job to overlap each other.
     */
    public function withoutOverlapping(int $expiresAt = 1440): self
    {
        $this->withoutOverlapping = true;
        $this->expiresAt = $expiresAt;

        return $this->skip(function (): bool {
            return $this->mutex->exists($this->getId());
        });
    }

    /**
     * State that the command should run in the background.
     */
    public function runInBackground(): self
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * Send the output of the command to a given location.
     */
    public function sendOutputTo(string $location, bool $append = false): self
    {
        $this->output = $location;
        $this->shouldAppendOutput = $append;

        return $this;
    }

    /**
     * Append the output of the command to a given location.
     */
    public function appendOutputTo(string $location): self
    {
        return $this->sendOutputTo($location, true);
    }

    /**
     * Register a callback to further filter the schedule.
     */
    public function when(\Closure $callback): self
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     */
    public function skip(\Closure $callback): self
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called before the operation.
     */
    public function before(\Closure $callback): self
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     */
    public function then(\Closure $callback): self
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Call all of the "after" callbacks for the job.
     */
    public function finish(ContainerInterface $container, int $exitCode): void
    {
        $this->exitCode = $exitCode;

        try {
            $this->callAfterCallbacks($container);
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * Determine if the filters pass for the job.
     */
    public function filtersPass(ContainerInterface $container): bool
    {
        $invoker = $container->get(InvokerInterface::class);

        foreach ($this->filters as $callback) {
            if (! $invoker->invoke($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if ($invoker->invoke($callback)) {
                return false;
            }
        }

        return true;
    }

    public function isDue(\DateTimeInterface $date): bool
    {
        return $this->expression->isDue($date);
    }

    public function on(\DateTimeInterface $time): self
    {
        $this->set(new DateTime($time));

        return $this;
    }

    /**
     * @internal
     */
    abstract public function run(ContainerInterface $container): void;

    abstract public function getSystemDescription(): string;

    /**
     * Get the id for the scheduled command.
     */
    abstract public function getId(): string;

    /**
     * Get the id for the scheduled command.
     */
    abstract public function getName(): string;

    /**
     * Call all of the "before" callbacks for the job.
     */
    final protected function callBeforeCallbacks(ContainerInterface $container): void
    {
        $invoker = $container->get(InvokerInterface::class);

        foreach ($this->beforeCallbacks as $callback) {
            $invoker->invoke($callback);
        }
    }

    /**
     * Call all of the "after" callbacks for the job.
     */
    final protected function callAfterCallbacks(ContainerInterface $container): void
    {
        $invoker = $container->get(InvokerInterface::class);

        foreach ($this->afterCallbacks as $callback) {
            $invoker->invoke($callback, [
                'exitCode' => $this->exitCode,
                'job' => $this,
            ]);
        }
    }

    /**
     * Delete the mutex for the job.
     */
    final protected function removeMutex(): void
    {
        if ($this->withoutOverlapping) {
            $this->mutex->forget($this->getId());
        }
    }

    private function set(PartValueInterface ...$values): self
    {
        $expression = clone $this->expression;

        foreach ($values as $value) {
            $value->updateExpression($expression);
        }

        $this->expression = $expression;

        return $this;
    }
}
