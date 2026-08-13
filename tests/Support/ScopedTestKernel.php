<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ScopedTestKernel extends TestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
        ]);
        $container->extension('doctrine', [
            'dbal' => [
                'default_connection' => 'covered',
                'connections' => [
                    'covered' => ['url' => 'sqlite:///:memory:'],
                    'plain' => ['url' => 'sqlite:///:memory:'],
                ],
            ],
        ]);
        $container->extension('rds_auth', [
            'connections' => ['covered'],
        ]);
    }
}
