<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TacticMedia\RdsAuthBundle\RdsAuthBundle;
use TacticMedia\RdsAuthBundle\RegionEnvVarProcessor;
use TacticMedia\RdsAuthBundle\Tests\Support\AsyncAwsRegionTestKernel;
use TacticMedia\RdsAuthBundle\Tests\Support\RegionEnvironmentIsolation;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
#[CoversClass(RegionEnvVarProcessor::class)]
final class AsyncAwsRegionTest extends KernelTestCase
{
    use RegionEnvironmentIsolation;

    protected static function getKernelClass(): string
    {
        return AsyncAwsRegionTestKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRegionEnvironment();
    }

    // The booted kernel registers an exception handler it does not remove; PHPUnit reports this as risky.
    protected function tearDown(): void
    {
        $this->restoreRegionEnvironment();
        parent::tearDown();
        restore_exception_handler();
    }

    #[TestDox('The AsyncAws bundle region backs rds_auth.region when no environment variable is set')]
    public function testFallsBackToTheAsyncAwsRegion(): void
    {
        self::assertSame('ap-southeast-2', $this->resolvedRegion());
    }

    #[TestDox('AWS_REGION wins over the AsyncAws bundle region')]
    public function testEnvironmentVariableWins(): void
    {
        $_ENV['AWS_REGION'] = $_SERVER['AWS_REGION'] = 'us-west-2';
        putenv('AWS_REGION=us-west-2');

        self::assertSame('us-west-2', $this->resolvedRegion());
    }

    private function resolvedRegion(): mixed
    {
        self::bootKernel(['debug' => false]);
        $container = self::getContainer();

        $getEnv = new \ReflectionMethod($container, 'getEnv');

        return $getEnv->invoke($container, 'rds_region:AWS_REGION');
    }
}
