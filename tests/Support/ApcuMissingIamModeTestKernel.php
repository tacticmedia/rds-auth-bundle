<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ApcuMissingIamModeTestKernel extends ApcuMissingTestKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->extension('rds_auth', ['iam_username' => 'app_user']);
    }
}
