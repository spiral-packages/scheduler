<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\App\Jobs;

use Spiral\Scheduler\Attribute\Schedule;

#[Schedule(
    expression: '@everyFifteenMinutes',
    name: 'Another simple job',
    description: 'Another simple job description'
)]
final class AnotherSimpleJobWithAttribute
{
    public function __invoke()
    {
    }
}
