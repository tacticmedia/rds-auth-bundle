<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TacticMedia\RdsAuthBundle\RdsAuthBundle;
use TacticMedia\RdsAuthBundle\RegionEnvVarProcessor;
use TacticMedia\RdsAuthBundle\Tests\Support\IamModeTestKernel;
use TacticMedia\RdsAuthBundle\Tests\Support\RegionEnvironmentIsolation;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
#[CoversClass(RegionEnvVarProcessor::class)]
final class IamModeTest extends KernelTestCase
{
    use RegionEnvironmentIsolation;

    protected static function getKernelClass(): string
    {
        return IamModeTestKernel::class;
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

    #[TestDox('IAM mode without any region source fails at the first connection with a message that names rds_auth.region')]
    public function testFailsWithAMessageThatNamesTheOption(): void
    {
        self::bootKernel(['debug' => false]);

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $registry);
        $connection = $registry->getConnection();
        self::assertInstanceOf(Connection::class, $connection);

        try {
            $connection->fetchOne('SELECT 1');
            self::fail('The connection must not succeed without a region.');
        } catch (\Throwable $throwable) {
            $messages = [];

            for ($current = $throwable; $current instanceof \Throwable; $current = $current->getPrevious()) {
                $messages[] = $current->getMessage();
            }

            self::assertStringContainsString('rds_auth.region', implode(' ', $messages));
        }
    }
}
