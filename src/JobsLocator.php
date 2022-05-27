<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Cron\CronExpression;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\Container;
use Spiral\Scheduler\Attribute\Schedule as ScheduleAttribute;
use Spiral\Scheduler\Job\CallbackJob;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Tokenizer\ClassesInterface;

class JobsLocator implements JobsLocatorInterface
{
    public function __construct(
        private readonly ClassesInterface $classes,
        private readonly ReaderInterface $reader,
        private readonly JobMutexInterface $mutex,
    ) {
    }

    public function getJobs(): array
    {
        $jobs = [];
        foreach ($this->classes->getClasses() as $class) {
            if ($schedule = $this->reader->firstClassMetadata($class, ScheduleAttribute::class)) {
                $jobs[$class->getName()] = $this->createJob($class, $schedule);
            }
        }

        return $jobs;
    }

    private function createJob(\ReflectionClass $class, ScheduleAttribute $schedule): CallbackJob
    {
        $className = $class->getName();
        $parameters = $schedule->parameters;

        $job = new CallbackJob(
            $this->mutex,
            new CronExpression(
                $schedule->expression
            ),
            $schedule->description,
            static function (Container $container) use ($className, $parameters) {
                $object = $container->make($className);

                $container->invoke([
                    $object,
                    \method_exists($object, 'run') ? 'run' : '__invoke',
                ], $parameters);
            }
        );

        if ($schedule->name) {
            $job->setName($schedule->name);
        }

        if ($schedule->runAs) {
            $job->runAs($schedule->runAs);
        }

        if ($schedule->withoutOverlapping === true) {
            $job->withoutOverlapping();
        } else if(\is_int($schedule->withoutOverlapping)) {
            $job->withoutOverlapping($schedule->withoutOverlapping);
        }

        if ($schedule->runInBackground) {
            $job->runInBackground();
        }

        return $job;
    }
}
