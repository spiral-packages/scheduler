<?php

declare(strict_types=1);

$generator = \Butschster\CronExpression\Generator::create();

return [
    'cacheStorage' => null,
    'queueConnection' => null,
    'timezone' => 'UTC',
    'expression' => [
        'aliases' => [
            '@everyFiveMinutes' => (string)$generator->everyFiveMinutes(),
            '@everyFifteenMinutes' => (string)$generator->everyFifteenMinutes(),
        ],
    ],
];
