<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TacticMedia\RdsAuth\RdsAuthDriver;
use TacticMedia\RdsAuthBundle\Tests\Support\TestKernel;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class RdsAuthBundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    // The booted kernel registers an exception handler it does not remove; PHPUnit reports this as risky.
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    #[TestDox('Default configuration wraps the default DBAL connection and queries execute')]
    public function testWrapsTheDefaultConnection(): void
    {
        self::bootKernel(['debug' => false]);

        $connection = $this->connection();

        self::assertInstanceOf(RdsAuthDriver::class, $connection->getDriver());
        self::assertEquals(1, $connection->fetchOne('SELECT 1'));
    }

    private function connection(): Connection
    {
        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $registry);
        $connection = $registry->getConnection();
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
