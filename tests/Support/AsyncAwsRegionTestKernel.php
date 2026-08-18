<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use AsyncAws\Symfony\Bundle\AsyncAwsBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class AsyncAwsRegionTestKernel extends TestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();
        yield new AsyncAwsBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->extension('rds_auth', ['iam_username' => 'app_user']);
        $container->extension('async_aws', ['config' => ['region' => 'ap-southeast-2']]);
    }
}
