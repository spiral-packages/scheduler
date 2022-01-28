<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Core\ResolverInterface;

final class ClosureInvoker
{
    public function __construct(private ResolverInterface $resolver)
    {
    }

    public function invoke(\Closure $callable, array $parameters = []): mixed
    {
        $refl = new \ReflectionFunction($callable);

        return $refl->invokeArgs(
            $this->resolver->resolveArguments($refl, $parameters)
        );
    }
}
