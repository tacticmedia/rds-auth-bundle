<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

// The bundle's code runs only at container compile time. A kernel cache from an
// earlier run would skip the compile, execute stale wiring, and zero the coverage.
$kernelCacheDirectory = sys_get_temp_dir().'/rds-auth-bundle-tests';

if (is_dir($kernelCacheDirectory)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($kernelCacheDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        \assert($file instanceof SplFileInfo);
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($kernelCacheDirectory);
}
