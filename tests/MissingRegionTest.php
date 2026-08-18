<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TacticMedia\RdsAuth\RdsAuthDriver;
use TacticMedia\RdsAuthBundle\RdsAuthBundle;
use TacticMedia\RdsAuthBundle\Tests\Support\RegionEnvironmentIsolation;
use TacticMedia\RdsAuthBundle\Tests\Support\TestKernel;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
final class MissingRegionTest extends KernelTestCase
{
    use RegionEnvironmentIsolation;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    // phpunit.xml.dist sets AWS_REGION; this class asserts behaviour without it.
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

    #[TestDox('Without AWS_REGION the middleware still wraps the connection and pass-through queries execute')]
    public function testWorksWithoutAwsRegion(): void
    {
        self::bootKernel(['debug' => false]);

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $registry);
        $connection = $registry->getConnection();
        self::assertInstanceOf(Connection::class, $connection);

        self::assertInstanceOf(RdsAuthDriver::class, $connection->getDriver());
        self::assertEquals(1, $connection->fetchOne('SELECT 1'));
    }
}
