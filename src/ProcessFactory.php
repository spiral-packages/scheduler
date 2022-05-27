<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Boot\DirectoriesInterface;
use Symfony\Component\Process\Process;

class ProcessFactory
{
    public function __construct(
        private readonly DirectoriesInterface $dirs
    ) {
    }

    public function createFromShellCommandline(string $command): Process
    {
        return Process::fromShellCommandline(
            command: $command,
            cwd: $this->dirs->get('root'),
            timeout: null
        );
    }
}
