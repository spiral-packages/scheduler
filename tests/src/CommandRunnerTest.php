<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Spiral\Scheduler\CommandRunner;
use Spiral\Scheduler\Exception\CommandRunnerException;
use Symfony\Component\Process\PhpExecutableFinder;

final class CommandRunnerTest extends TestCase
{
    private CommandRunner $runner;
    private \Mockery\MockInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runner = new CommandRunner(
            $this->finder = \Mockery::mock(PhpExecutableFinder::class)
        );
    }

    public function testGetsPhpBinary(): void
    {
        $this->finder->shouldReceive('find')->once()->andReturn('/path/to/php');
        $this->assertSame('/path/to/php', $this->runner->phpBinary());
    }

    public function testNonExistPhpBinaryShouldThrowAnException(): void
    {
        $this->expectException(CommandRunnerException::class);
        $this->expectExceptionMessage('PHP binary not found.');

        $this->finder->shouldReceive('find')->once()->andReturnFalse();
        $this->runner->phpBinary();
    }
}
