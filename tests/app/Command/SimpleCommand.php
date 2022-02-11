<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\App\Command;

use Spiral\Console\Command;

class SimpleCommand extends Command
{
    protected const NAME = 'foo:bar';
    protected const DESCRIPTION = 'Simple command';
}
