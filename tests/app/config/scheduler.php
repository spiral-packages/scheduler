<?php

declare(strict_types=1);

$generator = \Butschster\CronExpression\Generator::create();

return [
    'cacheStorage' => null,
    'queueConnection' => 'queue-test',
    'timezone' => 'UTC',
    'expression' => [
        'aliases' => [
            '@everySixHours' => (string)$generator->everySixHours(),
            '@everyFifteenMinutes' => (string)$generator->everyFifteenMinutes(),
        ],
    ],
];
