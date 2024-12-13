<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Attribute;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

#[\Attribute(\Attribute::TARGET_CLASS), NamedArgumentConstructor]
class Schedule
{
    public function __construct(
        public readonly string $name,
        public readonly string $expression = '* * * * *',
        public readonly ?string $description = null,
        public readonly ?string $runAs = null,
        public readonly bool|int $withoutOverlapping = false,
        public readonly bool $runInBackground = false,
        public readonly array $parameters = [],
    ) {}
}
