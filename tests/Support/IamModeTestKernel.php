<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class IamModeTestKernel extends TestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->extension('rds_auth', ['iam_username' => 'app_user']);
    }
}
