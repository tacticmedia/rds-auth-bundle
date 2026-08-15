<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use TacticMedia\RdsAuthBundle\RdsAuthBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new RdsAuthBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/rds-auth-bundle-tests/'.self::VERSION.'/'.str_replace('\\', '_', static::class).'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/rds-auth-bundle-tests/'.self::VERSION.'/'.str_replace('\\', '_', static::class).'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
        ]);
        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///:memory:'],
        ]);
        $container->extension('rds_auth', []);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
