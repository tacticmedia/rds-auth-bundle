<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

trait RegionEnvironmentIsolation
{
    /** @var array<string, string> */
    private array $savedRegionEnvironment = [];

    private function clearRegionEnvironment(): void
    {
        foreach (['AWS_REGION', 'AWS_DEFAULT_REGION'] as $variable) {
            $value = $_SERVER[$variable] ?? $_ENV[$variable] ?? getenv($variable);

            if (is_string($value)) {
                $this->savedRegionEnvironment[$variable] = $value;
            }

            unset($_ENV[$variable], $_SERVER[$variable]);
            putenv($variable);
        }
    }

    private function restoreRegionEnvironment(): void
    {
        foreach ($this->savedRegionEnvironment as $variable => $value) {
            $_ENV[$variable] = $_SERVER[$variable] = $value;
            putenv($variable.'='.$value);
        }

        $this->savedRegionEnvironment = [];
    }
}
