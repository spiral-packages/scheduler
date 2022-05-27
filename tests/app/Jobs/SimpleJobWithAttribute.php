<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\App\Jobs;

use Spiral\Scheduler\Attribute\Schedule;

#[Schedule(
    name: 'Simple job',
    expression: '@everySixHours',
    description: 'Simple job description'
)]
final class SimpleJobWithAttribute
{
    public function __invoke()
    {
    }
}
