<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Spiral\Scheduler\CommandBuilder;
use Spiral\Scheduler\CommandRunner;
use Symfony\Component\Process\PhpExecutableFinder;

final class CommandBuilderTest extends TestCase
{
    private CommandBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new CommandBuilder(
            new CommandRunner(
                $finder = $this->mockContainer(PhpExecutableFinder::class)
            )
        );

        $finder->shouldReceive('find')->with(false)->andReturn('/usr/bin/php');
    }

    public function testBuildForegroundCommand(): void
    {
        $this->assertSame(
            "foo:bar > '/dev/null' 2>&1",
            $this->builder->buildForegroundCommand('foo:bar')
        );
    }

    public function testBuildForegroundCommandWithAppendOutput(): void
    {
        $this->assertSame(
            "foo:bar >> '/dev/null' 2>&1",
            $this->builder->buildForegroundCommand('foo:bar', appendOutput: true)
        );
    }

    public function testBuildForegroundCommandWithOutput(): void
    {
        $this->assertSame(
            "foo:bar > '/foo/bar' 2>&1",
            $this->builder->buildForegroundCommand('foo:bar', output: '/foo/bar')
        );
    }

    public function testBuildForegroundCommandWithUser(): void
    {
        $this->assertSame(
            "sudo -u root -- sh -c 'foo:bar > '/dev/null' 2>&1'",
            $this->builder->buildForegroundCommand('foo:bar', user: 'root')
        );
    }

    public function testBuildBackgroundCommand(): void
    {
        $this->assertSame(
            "(foo:bar > '/dev/null' 2>&1 ; /usr/bin/php app.php schedule:finish \"foo-id\" \"$?\") > '/dev/null' 2>&1 &",
            $this->builder->buildBackgroundCommand('foo:bar', 'foo-id')
        );
    }

    public function testBuildBackgroundCommandWithAppendOutput(): void
    {
        $this->assertSame(
            "(foo:bar >> '/dev/null' 2>&1 ; /usr/bin/php app.php schedule:finish \"foo-id\" \"$?\") > '/dev/null' 2>&1 &",
            $this->builder->buildBackgroundCommand('foo:bar', 'foo-id', appendOutput: true)
        );
    }

    public function testBuildBackgroundCommandWithOutput(): void
    {
        $this->assertSame(
            "(foo:bar > '/foo/bar' 2>&1 ; /usr/bin/php app.php schedule:finish \"foo-id\" \"$?\") > '/dev/null' 2>&1 &",
            $this->builder->buildBackgroundCommand('foo:bar', 'foo-id', output: '/foo/bar')
        );
    }

    public function testBuildBackgroundCommandWithUser(): void
    {
        $this->assertSame(
            "sudo -u root -- sh -c '(foo:bar > '/dev/null' 2>&1 ; /usr/bin/php app.php schedule:finish \"foo-id\" \"$?\") > '/dev/null' 2>&1 &'",
            $this->builder->buildBackgroundCommand('foo:bar', 'foo-id', user: 'root')
        );
    }
}
