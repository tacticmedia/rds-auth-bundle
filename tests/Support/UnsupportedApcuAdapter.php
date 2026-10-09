<?php

declare(strict_types=1);

namespace TacticMedia\RdsAuthBundle\Tests\Support;

use Symfony\Component\Cache\Adapter\ApcuAdapter;

/**
 * Its constructor throws the CacheException of a host without ext-apcu.
 */
final class UnsupportedApcuAdapter extends ApcuAdapter
{
    public static function isSupported(): bool
    {
        return false;
    }
}
