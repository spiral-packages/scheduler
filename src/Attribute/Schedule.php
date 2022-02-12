<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

#[
    Attribute(Attribute::TARGET_CLASS),
    NamedArgumentConstructor
]
class Schedule
{
    public function __construct(
        public string $name,
        public string $expression = '* * * * *',
        public ?string $description = null,
        public ?string $runAs = null,
        public bool|int $withoutOverlapping = false,
        public bool $runInBackground = false,
        public array $parameters = []
    ) {
    }
}
