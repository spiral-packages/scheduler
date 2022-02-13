<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Scheduler\Exception\CommandRunnerException;
use Symfony\Component\Process\PhpExecutableFinder;

final class CommandRunner
{
    public function __construct(
        private PhpExecutableFinder $phpFinder
    ) {
    }

    public function run(string $command)
    {
    }

    /**
     * Determine the proper PHP executable.
     * @throws CommandRunnerException
     */
    public function phpBinary(): string
    {
        $binary = $this->phpFinder->find(false);

        if (! $binary) {
            throw new CommandRunnerException('PHP binary not found.');
        }

        return $binary;
    }

    /**
     * Determine the proper spiral binary executable.
     */
    public function spiralBinary(): string
    {
        return defined('SPIRAL_BINARY') ? SPIRAL_BINARY : 'app.php';
    }

    /**
     * Format the given command as a fully-qualified executable command.
     */
    public function formatCommandString(string $string): string
    {
        return sprintf('%s %s %s', $this->phpBinary(), $this->spiralBinary(), $string);
    }
}
