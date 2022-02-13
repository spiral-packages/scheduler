<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\App;

interface PingerInterface
{
    public function ping(string $url): bool;
}
