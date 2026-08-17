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
use TacticMedia\RdsAuthBundle\Tests\Support\NoEventDispatcherTestKernel;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
final class NoEventDispatcherTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return NoEventDispatcherTestKernel::class;
    }

    // The booted kernel registers an exception handler it does not remove; PHPUnit reports this as risky.
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    #[TestDox('A null event_dispatcher disables the dispatch; the driver is still wrapped and queries execute')]
    public function testNullEventDispatcherStillWraps(): void
    {
        self::bootKernel(['debug' => false]);

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $registry);
        $connection = $registry->getConnection();
        self::assertInstanceOf(Connection::class, $connection);

        $driver = $connection->getDriver();
        self::assertInstanceOf(RdsAuthDriver::class, $driver);
        self::assertEquals(1, $connection->fetchOne('SELECT 1'));

        $dispatcher = new \ReflectionProperty(RdsAuthDriver::class, 'eventDispatcher');
        self::assertNull($dispatcher->getValue($driver));
    }
}
