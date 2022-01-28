<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

final class CommandBuilder
{
    public function __construct(private CommandRunner $commandRunner)
    {
    }

    public function buildForegroundCommand(
        string $command,
        bool $shouldAppendOutput = false,
        string $output = '/dev/null',
        ?string $user = null
    ): string {
        $output = ProcessUtils::escapeArgument($output);

        return $this->ensureCorrectUser(
            $command.($shouldAppendOutput ? ' >> ' : ' > ').$output.' 2>&1',
            $user
        );
    }

    public function buildBackgroundCommand(
        string $command,
        string $id,
        bool $shouldAppendOutput = false,
        string $output = '/dev/null',
        ?string $user = null
    ): string {
        $output = ProcessUtils::escapeArgument($output);

        $redirect = $shouldAppendOutput ? ' >> ' : ' > ';

        $finished = $this->commandRunner->formatCommandString('schedule:finish').' "'.$id.'"';

        return $this->ensureCorrectUser(
            '('.$command.$redirect.$output.' 2>&1 ; '.$finished.' "$?") > '
            .ProcessUtils::escapeArgument('/dev/null').' 2>&1 &',
            $user
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     */
    protected function ensureCorrectUser(string $command, ?string $user = null): string
    {
        return $user ? 'sudo -u '.$user.' -- sh -c \''.$command.'\'' : $command;
    }
}
