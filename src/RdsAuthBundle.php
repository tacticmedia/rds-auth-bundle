<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use TacticMedia\RdsAuth\RdsAuthMiddleware;
use TacticMedia\RdsAuth\RdsIamTokenProvider;
use TacticMedia\RdsAuth\RdsSecretPasswordProvider;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class RdsAuthBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $children = $rootNode->children();

        $children->scalarNode('region')
            ->info('AWS region of the RDS endpoint and the secret.')
            ->cannotBeEmpty()
            ->defaultValue('%env(AWS_REGION)%')
        ;

        $children->scalarNode('iam_username')
            ->info('Database user for RDS IAM token authentication. Null or empty disables IAM authentication.')
            ->defaultValue('%env(default::RDS_IAM_USERNAME)%')
        ;

        $children->scalarNode('secret_arn')
            ->info('ARN of the RDS-managed master-password secret. Null or empty disables the master-password refresh.')
            ->defaultValue('%env(default::RDS_SECRET_ARN)%')
        ;

        $children->scalarNode('cache_pool')
            ->info('Cache pool service id that stores accepted credentials. Null or empty disables caching.')
            ->defaultValue('cache.app')
        ;

        $children->scalarNode('event_dispatcher')
            ->info('Event dispatcher service id that receives the ConfiguredPasswordOutdated event. Null or empty disables the dispatch.')
            ->defaultValue('event_dispatcher')
        ;

        $children->arrayNode('connections')
            ->info('DBAL connection names the middleware applies to. An empty list applies it to every connection.')
            ->scalarPrototype()
        ;
    }

    /** @param array{region: string, iam_username: ?string, secret_arn: ?string, cache_pool: ?string, event_dispatcher: ?string, connections: list<string>} $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set('rds_auth.token_provider', RdsIamTokenProvider::class)
            ->args([$config['region']])
        ;

        $services->set('rds_auth.password_provider', RdsSecretPasswordProvider::class)
            ->args([$config['region']])
        ;

        $pool = $config['cache_pool'];
        $dispatcher = $config['event_dispatcher'];

        $middleware = $services->set('rds_auth.middleware', RdsAuthMiddleware::class)
            ->args([
                service('rds_auth.token_provider'),
                service('rds_auth.password_provider'),
                $config['iam_username'],
                $config['secret_arn'],
                null !== $pool && '' !== $pool ? service($pool) : null,
                null !== $dispatcher && '' !== $dispatcher ? service($dispatcher) : null,
            ])
        ;

        // A doctrine.middleware tag without a connection attribute applies to all connections:
        // https://symfony.com/bundles/DoctrineBundle/current/middlewares.html
        if ([] === $config['connections']) {
            $middleware->tag('doctrine.middleware');

            return;
        }

        foreach ($config['connections'] as $connection) {
            $middleware->tag('doctrine.middleware', ['connection' => $connection]);
        }
    }
}
