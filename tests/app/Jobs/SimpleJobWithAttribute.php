<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\App\Jobs;

use Spiral\Scheduler\Attribute\Schedule;

#[Schedule(
    expression: '@everySixHours',
    name: 'Simple job',
    description: 'Simple job description'
)]
final class SimpleJobWithAttribute
{
    public function __invoke()
    {
    }
}
