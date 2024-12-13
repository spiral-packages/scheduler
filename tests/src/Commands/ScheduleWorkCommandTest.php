<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Commands;

use Spiral\Scheduler\PeriodicCommandRunnerInterface;
use Spiral\Scheduler\Tests\TestCase;

final class ScheduleWorkCommandTest extends TestCase
{
    public function testWorker(): void
    {
        $runner = $this->mockContainer(PeriodicCommandRunnerInterface::class);
        $runner->shouldReceive('run')->once()->withSomeOfArgs('schedule:run')->andReturnUsing(
            static function (string $command, \Closure $onSuccess, \Closure $onError): void {
                foreach ([1, 2] as $tick) {
                    if ($tick === 1) {
                        $onSuccess('Job handled');
                    }

                    if ($tick === 2) {
                        $onError('Job error');
                    }
                }
            },
        );

        $this->assertConsoleCommandOutputContainsStrings('schedule:work', [], [
            'Schedule worker started successfully.', 'Job handled', 'Job error',
        ]);
    }
}
