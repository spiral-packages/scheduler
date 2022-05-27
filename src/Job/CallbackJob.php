<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Job;

use Cron\CronExpression;
use Spiral\Core\Container;
use Spiral\Core\InvokerInterface;
use Spiral\Queue\QueueConnectionProviderInterface;
use Spiral\Scheduler\CommandUtils;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Throwable;

final class CallbackJob extends Job
{
    private ?string $name = null;

    public function __construct(
        JobMutexInterface $mutex,
        CronExpression $expression,
        protected ?string $description,
        private readonly \Closure $callback,
        private readonly array $parameters = []
    ) {
        parent::__construct($mutex, $expression);
    }

    public function run(Container $container): void
    {
        if ($this->withoutOverlapping && ! $this->mutex->create($this->getId(), $this->getExpiresAt())) {
            return;
        }

        parent::callBeforeCallbacks($container);

        try {
            $callback = $this->callback;
            $params = $this->parameters;
            $id = $this->getId();

            if ($this->runInBackground) {
                /** @var QueueConnectionProviderInterface $manager */
                $config = $container->get(SchedulerConfig::class);
                $queue = $container->get(QueueConnectionProviderInterface::class)
                    ->getConnection($config->getQueueConnection());

                $queue->pushCallable(static function (
                    InvokerInterface $invoker,
                    JobMutexInterface $mutex
                ) use ($callback, $params, $id): void {
                    $invoker->invoke($callback, $params);
                    $mutex->forget($id);
                });
            } else {
                $container->invoke($callback, $params);
                $this->removeMutex();
            }
        } catch (Throwable $e) {
            $this->removeMutex();
            throw $e;
        }
    }

    public function getId(): string
    {
        return 'schedule-'.\sha1($this->getExpression().$this->description);
    }

    public function getSystemDescription(): string
    {
        return 'Callback job: '.$this->getId();
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return 'callback: '.CommandUtils::compileParameters(array_keys($this->parameters));
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
