<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class ApcuMissingTestKernel extends TestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->services()->set('test.apcu_pool', UnsupportedApcuAdapter::class);
        $container->extension('rds_auth', ['cache_pool' => 'test.apcu_pool']);
    }
}
