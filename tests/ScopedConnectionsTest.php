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
use TacticMedia\RdsAuthBundle\Tests\Support\ScopedTestKernel;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
final class ScopedConnectionsTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return ScopedTestKernel::class;
    }

    // The booted kernel registers an exception handler it does not remove; PHPUnit reports this as risky.
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    #[TestDox('The connections option limits the middleware to the listed connections')]
    public function testOnlyListedConnectionsAreWrapped(): void
    {
        self::bootKernel(['debug' => false]);

        self::assertInstanceOf(RdsAuthDriver::class, $this->connection('covered')->getDriver());
        self::assertNotInstanceOf(RdsAuthDriver::class, $this->connection('plain')->getDriver());
    }

    private function connection(string $name): Connection
    {
        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $registry);
        $connection = $registry->getConnection($name);
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
