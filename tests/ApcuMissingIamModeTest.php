<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Exception\CacheException;
use TacticMedia\RdsAuthBundle\RdsAuthBundle;
use TacticMedia\RdsAuthBundle\Tests\Support\ApcuMissingIamModeTestKernel;

/**
 * @internal
 */
#[CoversClass(RdsAuthBundle::class)]
final class ApcuMissingIamModeTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return ApcuMissingIamModeTestKernel::class;
    }

    // The booted kernel registers an exception handler it does not remove; PHPUnit reports this as risky.
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    #[TestDox('IAM mode constructs the configured pool at the first connect, so a missing ext-apcu fails there')]
    public function testCredentialModeConstructsThePoolAtConnect(): void
    {
        self::bootKernel(['debug' => false]);

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(Registry::class, $registry);
        $connection = $registry->getConnection();
        self::assertInstanceOf(Connection::class, $connection);

        try {
            $connection->fetchOne('SELECT 1');
            self::fail('The connection must not succeed while the pool cannot be constructed.');
        } catch (\Throwable $throwable) {
            $messages = [];

            for ($current = $throwable; $current instanceof \Throwable; $current = $current->getPrevious()) {
                if ($current instanceof CacheException) {
                    $messages[] = $current->getMessage();
                }
            }

            self::assertSame(['APCu is not enabled.'], $messages);
        }
    }
}
