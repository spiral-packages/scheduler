<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Spiral\Attributes\AttributeReader;
use Spiral\Scheduler\JobsLocator;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Scheduler\Tests\App\Jobs\AnotherSimpleJobWithAttribute;
use Spiral\Scheduler\Tests\App\Jobs\JobWithoutAttributes;
use Spiral\Scheduler\Tests\App\Jobs\SimpleJobWithAttribute;
use Spiral\Tokenizer\ClassesInterface;

final class JobsLocatorTest extends TestCase
{
    public function testLocateJobs()
    {
        $locator = new JobsLocator(
            $classes = $this->mockContainer(ClassesInterface::class),
            new AttributeReader(),
            $this->mockContainer(JobMutexInterface::class)
        );

        $classes->shouldReceive('getClasses')->once()->andReturn([
            new \ReflectionClass(SimpleJobWithAttribute::class),
            new \ReflectionClass(AnotherSimpleJobWithAttribute::class),
            new \ReflectionClass(JobWithoutAttributes::class),
        ]);

        $this->assertCount(2, $jobs = $locator->getJobs());

        $this->assertSame('Simple job', $jobs[SimpleJobWithAttribute::class]->getName());
        $this->assertSame('Simple job description', $jobs[SimpleJobWithAttribute::class]->getDescription());
        $this->assertSame('0 */6 * * *', $jobs[SimpleJobWithAttribute::class]->getExpression());

        $this->assertSame('Another simple job', $jobs[AnotherSimpleJobWithAttribute::class]->getName());
        $this->assertSame('Another simple job description', $jobs[AnotherSimpleJobWithAttribute::class]->getDescription());
        $this->assertSame('*/15 * * * *', $jobs[AnotherSimpleJobWithAttribute::class]->getExpression());
    }
}
