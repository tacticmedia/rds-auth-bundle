<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

/**
 * Resolves `%env(rds_region:AWS_REGION)%`: the named variable, then
 * AWS_DEFAULT_REGION, then the AsyncAws bundle's global region. Empty
 * strings count as unset. The final failure names every configuration
 * option instead of Symfony's bare variable-not-found message.
 */
final readonly class RegionEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(private ?string $asyncAwsRegion)
    {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        foreach ([$name, 'AWS_DEFAULT_REGION'] as $variable) {
            try {
                $value = $getEnv($variable);
            } catch (EnvNotFoundException) {
                continue;
            }

            if (\is_string($value) && '' !== $value) {
                return $value;
            }
        }

        if (null !== $this->asyncAwsRegion && '' !== $this->asyncAwsRegion) {
            return $this->asyncAwsRegion;
        }

        throw new EnvNotFoundException('RDS authentication needs an AWS region. Set the rds_auth.region option, the AWS_REGION environment variable, or async_aws.config.region when the AsyncAws bundle is installed.');
    }

    public static function getProvidedTypes(): array
    {
        return ['rds_region' => 'string'];
    }
}
