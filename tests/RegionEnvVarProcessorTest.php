<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;
use TacticMedia\RdsAuthBundle\RegionEnvVarProcessor;

/**
 * @internal
 */
#[CoversClass(RegionEnvVarProcessor::class)]
final class RegionEnvVarProcessorTest extends TestCase
{
    #[TestDox('Returns the named variable when it is set')]
    public function testReturnsTheNamedVariable(): void
    {
        $value = (new RegionEnvVarProcessor(null))->getEnv('rds_region', 'AWS_REGION', $this->environment(['AWS_REGION' => 'ap-southeast-2']));

        self::assertSame('ap-southeast-2', $value);
    }

    #[TestDox('Falls back to AWS_DEFAULT_REGION when the named variable is missing')]
    public function testFallsBackToDefaultRegionVariable(): void
    {
        $value = (new RegionEnvVarProcessor(null))->getEnv('rds_region', 'AWS_REGION', $this->environment(['AWS_DEFAULT_REGION' => 'eu-west-1']));

        self::assertSame('eu-west-1', $value);
    }

    #[TestDox('Treats an empty variable as unset')]
    public function testTreatsEmptyAsUnset(): void
    {
        $value = (new RegionEnvVarProcessor('us-west-2'))->getEnv('rds_region', 'AWS_REGION', $this->environment(['AWS_REGION' => '', 'AWS_DEFAULT_REGION' => '']));

        self::assertSame('us-west-2', $value);
    }

    #[TestDox('Falls back to the AsyncAws region when no variable is set')]
    public function testFallsBackToTheAsyncAwsRegion(): void
    {
        $value = (new RegionEnvVarProcessor('us-west-2'))->getEnv('rds_region', 'AWS_REGION', $this->environment([]));

        self::assertSame('us-west-2', $value);
    }

    #[TestDox('Names every configuration option when no source provides a region')]
    public function testNamesTheOptionsInTheFailure(): void
    {
        $processor = new RegionEnvVarProcessor(null);

        $this->expectException(EnvNotFoundException::class);
        $this->expectExceptionMessage('rds_auth.region');
        $processor->getEnv('rds_region', 'AWS_REGION', $this->environment([]));
    }

    /** @param array<string, string> $variables */
    private function environment(array $variables): \Closure
    {
        return static fn (string $name): string => $variables[$name] ?? throw new EnvNotFoundException($name);
    }
}
