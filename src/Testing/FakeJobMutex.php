<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Testing;

use PHPUnit\Framework\TestCase;
use Spiral\Scheduler\Mutex\JobMutexInterface;

final class FakeJobMutex implements JobMutexInterface
{
    private array $created = [];
    private array $deleted = [];

    public function assertCreated(string $id, ?int $minutes = null): void
    {
        TestCase::assertArrayHasKey(
            $id,
            $this->created,
            \sprintf('The expected [%s] job mutex was not created.', $id),
        );

        if ($minutes !== null) {
            TestCase::assertSame(
                $minutes,
                $this->created[$id],
                \sprintf(
                    'The expected [%s] job mutex jas different ttl [%d], but expected [%d].',
                    $id,
                    $this->created[$id],
                    $minutes,
                ),
            );
        }
    }

    public function assertNothingCreated(): void
    {
        $count = \count($this->created);

        TestCase::assertSame(
            0,
            $count,
            \sprintf('%d unexpected mutexes were created.', $count),
        );
    }

    public function assertForgotten(string $id): void
    {
        $this->assertCreated($id);

        TestCase::assertArrayHasKey(
            $id,
            $this->deleted,
            \sprintf('The expected [%s] job mutex was not forgotten.', $id),
        );
    }

    public function create(string $id, int $minutes): bool
    {
        $this->created[$id] = $minutes;
    }

    public function exists(string $id): bool
    {
        return isset($this->created[$id]);
    }

    public function forget(string $id): void
    {
        $this->deleted[] = $id;
    }
}
