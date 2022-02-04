# This is my package scheduler

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral-packages/scheduler.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/scheduler)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spiral-packages/scheduler/run-tests?label=tests)](https://github.com/spiral-packages/scheduler/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral-packages/scheduler.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/scheduler)

This is a cron jobs scheduler that can be easily integrated with your project based on spiral framework. The idea was
originally inspired by the Laravel Task Scheduling.

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.0+
- Spiral framework 2.9+

## Installation

You can install the package via composer:

```bash
composer require spiral-packages/scheduler
```

After package install you need to add bootloader from the package to your application.

```php
use Spiral\Scheduler\Bootloader\SchedulerBootloader;

protected const LOAD = [
    // ...
    SchedulerBootloader::class,
];
```

At first you need to create config file `app/config/scheduler.php`

```php
<?php

declare(strict_types=1);

return [
    'queueConnection' => env('SCHEDULER_QUEUE_CONNECTION', 'sync'),
    'cacheStorage' => env('SCHEDULER_CACHE_STORAGE', 'redis'), // for mutexes
];
```

Add a cron configuration entry to our server that runs the `schedule:run` command every minute. 

```bash
* * * * * cd /path-to-your-project && php app.php schedule:run >> /dev/null 2>&1
```

## Usage

Create a new bootloader, for example, `SchedulerBootloader` in your application

```php
use Spiral\Boot\Bootloader\Bootloader;
use App\Scheduling\Schedule;
use Psr\Log\LoggerInterface;

final class SchedulerBootloader extends Bootloader
{
    public function start(Schedule $schedule): void
    {
        $schedule->command('ping', ['https://ya.ru'])
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(directory('runtime').'logs/cron.log');
            
        $schedule->call('Ping url', static function (LoggerInterface $logger, string $url) {
            $headers = @get_headers($url);
            $status = $headers && strpos($headers[0], '200');

            $logger->info(sprintf('URL: %s %s', $url, $status ? 'Exists' : 'Does not exist'));

            return $status;
        }, ['url' => 'https://google.com'])->everyFiveMinutes()->withoutOverlapping();
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [butschster](https://github.com/spiral-packages)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
