# Cron jobs scheduler for Spiral Framework

[![PHP](https://img.shields.io/packagist/php-v/spiral-packages/scheduler.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/scheduler)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral-packages/scheduler.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/scheduler)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spiral-packages/scheduler/run-tests?label=tests&style=flat-square)](https://github.com/spiral-packages/scheduler/actions?query=workflow%3Arun-tests)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral-packages/scheduler.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/scheduler)

This is a cron jobs scheduler that can be easily integrated with your project based on spiral framework. The idea was
originally inspired by the Laravel Task Scheduling.

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+
- Spiral framework 3.0+

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

$generator = \Butschster\CronExpression\Generator::create();

return [
    'queueConnection' => env('SCHEDULER_QUEUE_CONNECTION', 'sync'),
    'cacheStorage' => env('SCHEDULER_CACHE_STORAGE', 'redis'), // for mutexes
    'timezone' => 'UTC',
    'expression' => [
        'aliases' => [
            '@everyFiveMinutes' => (string)$generator->everyFiveMinutes(),
            '@everyFifteenMinutes' => (string)$generator->everyFifteenMinutes(),
        ],
    ],
];
```

Add a cron configuration entry to our server that runs the `schedule:run` command every minute.

```bash
* * * * * cd /path-to-your-project && php app.php schedule:run >> /dev/null 2>&1
```

If you don't have crontab or you want to run schedule via RoadRunner, you may use the `schedule:work` command.This
command will run in the foreground and invoke the scheduler every minute until you terminate the command:

```bash
php app.php schedule:work
```

Or via RoadRunner

```yaml
service:
  cron_worker:
    command: "php app.php schedule:work"
    process_num: 1
    exec_timeout: 0
    remain_after_exit: true
    restart_sec: 1
```

Read more about RoadRunner configuration in the [official documentation](https://roadrunner.dev/docs/beep-beep-service)

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
        // Run command by name
        $schedule->command('ping', ['https://google.com'])
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(directory('runtime').'logs/cron.log');
            
            
        // Run command by class
        $schedule->command(Command\PingCommand::class, ['https://google.com'])
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(directory('runtime').'logs/cron.log');
            
        // Run callable command
        $schedule->call('Ping url', static function (LoggerInterface $logger, string $url) {
            $headers = @get_headers($url);
            $status = $headers && strpos($headers[0], '200');

            $logger->info(sprintf('URL: %s %s', $url, $status ? 'Exists' : 'Does not exist'));

            return $status;
        }, ['url' => 'https://google.com'])->everyFiveMinutes()->withoutOverlapping();
    }
}
```

You can also register scheduler jobs via PHP attributes

```php
use Spiral\Scheduler\Attribute\Schedule;

#[Schedule(
    expression: '@everyFiveMinutes',
    name: 'Ping url', 
    parameters: ['url' => 'https://google.com'],
    withoutOverlapping: true,
    runAs: 'root',
    runInBackground: true
)]
class SimpleJob
{
    public function __construct(
        private LoggerInterface $logger
    )  {
        
    }
    
    public function run(LoggerInterface $logger, string $url)
    {
        $headers = @get_headers($url);
        $status = $headers && \strpos($headers[0], '200');

        $this->logger->info(\sprintf('URL: %s %s', $url, $status ? 'Exists' : 'Does not exist'));
    }
}
```

## Testing

```bash
composer test
```

If you are using `spiral/testing` package in your application, you can additionally use
trait `Spiral\Scheduler\Testing\InteractsWithSchedule` in your tests cases.

```php
class MyJobSchedulingTest extends TestCase
{
    use \Spiral\Scheduler\Testing\InteractsWithSchedule;

    public function testCheckIfJobRun(): void
    {
        $scheduler = $this->runScheduler('*/15 * * * *');
        
        $scheduler->assertHandled(function (\Spiral\Scheduler\Job\Job $job) {
            return $job->getName() === 'My super job';
        });
        
        $scheduler->assertHandledTotalJobs(5);
    }
}
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
