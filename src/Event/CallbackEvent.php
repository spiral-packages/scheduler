<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Event;

use Spiral\Core\Container;
use Spiral\Queue\QueueConnectionProviderInterface;
use Spiral\Scheduler\ClosureInvoker;
use Spiral\Scheduler\CommandUtils;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\Mutex\EventMutexInterface;
use Throwable;

final class CallbackEvent extends Event
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        EventMutexInterface $mutex,
        protected ?string $description,
        private \Closure $callback,
        private array $parameters = []
    ) {
        parent::__construct($mutex);
    }

    public function run(Container $container): void
    {
        if ($this->withoutOverlapping && ! $this->mutex->create($this->getId(), $this->getExpiresAt())) {
            return;
        }

        parent::callBeforeCallbacks($container);

        try {
            /** @var QueueConnectionProviderInterface $manager */
            $manager = $container->get(QueueConnectionProviderInterface::class);
            $config = $container->get(SchedulerConfig::class);

            $callback = $this->callback;
            $params = $this->parameters;
            $id = $this->getId();

            $manager->getConnection($config->getCacheStorage())->pushCallable(function (
                ClosureInvoker $invoker,
                EventMutexInterface $mutex
            ) use ($callback, $params, $id) {
                $invoker->invoke($callback, $params);
                $mutex->forget($id);
            });
        } catch (Throwable $e) {
            $this->removeMutex();
            throw $e;
        }
    }

    public function getId(): string
    {
        return 'schedule-'.sha1($this->getExpression().$this->description);
    }

    public function getSystemDescription(): string
    {
        return 'Callback event: '.$this->getId();
    }

    public function getName(): string
    {
        return 'callback: '.CommandUtils::compileParameters(array_keys($this->parameters));
    }
}
