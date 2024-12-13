<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Spiral\Boot\DirectoriesInterface;
use Spiral\Scheduler\ProcessFactory;

final class ProcessFactoryTest extends TestCase
{
    public function testCreatesFromShellCommandline(): void
    {
        $factory = new ProcessFactory(
            $dirs = $this->mockContainer(DirectoriesInterface::class),
        );

        $dirs->shouldReceive('get')->once()->with('root')->andReturn('/path/to/project');

        $process = $factory->createFromShellCommandline('/usr/bin/php foo:bar');

        $this->assertSame('/usr/bin/php foo:bar', $process->getCommandLine());
        $this->assertSame('/path/to/project', $process->getWorkingDirectory());
        $this->assertNull($process->getTimeout());
    }
}
