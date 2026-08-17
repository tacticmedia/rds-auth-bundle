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
use TacticMedia\RdsAuthBundle\Tests\Support\NoCacheTestKernel;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
final class NoCachePoolTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return NoCacheTestKernel::class;
    }

    // The booted kernel registers an exception handler it does not remove; PHPUnit reports this as risky.
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    #[TestDox('A null cache_pool disables caching; the driver is still wrapped and queries execute')]
    public function testNullCachePoolStillWraps(): void
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
