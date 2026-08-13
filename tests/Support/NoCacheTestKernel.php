<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class NoCacheTestKernel extends TestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
        ]);
        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///:memory:'],
        ]);
        $container->extension('rds_auth', [
            'cache_pool' => null,
        ]);
    }
}
