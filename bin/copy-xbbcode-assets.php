<?php

declare(strict_types=1);

/**
 * Publishes the static assets bundled with gemorroj/xbbcode into public/.
 *
 * The library ships its stylesheet and smile images under resources/; they
 * are not autoloaded PHP files, so a web application has to expose them from
 * its public directory. Running this after composer install/update keeps the
 * public copy in sync with the installed package version.
 */
$projectDir = \dirname(__DIR__);
$source = $projectDir.'/vendor/gemorroj/xbbcode/resources';

if (!\is_dir($source)) {
    \fwrite(\STDERR, "xBBCode resources directory was not found: {$source}. Skipping.\n");
    exit(0);
}

$target = $projectDir.'/public/resources/xbbcode/resources';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);

$copied = 0;

foreach ($iterator as $item) {
    $relativePath = \substr($item->getPathname(), \strlen($source) + 1);
    $destination = $target.\DIRECTORY_SEPARATOR.$relativePath;

    if ($item->isDir()) {
        if (!\is_dir($destination) && !\mkdir($destination, 0o775, true) && !\is_dir($destination)) {
            throw new RuntimeException('Unable to create directory: '.$destination);
        }

        continue;
    }

    $destinationDirectory = \dirname($destination);

    if (!\is_dir($destinationDirectory) && !\mkdir($destinationDirectory, 0o775, true) && !\is_dir($destinationDirectory)) {
        throw new RuntimeException('Unable to create directory: '.$destinationDirectory);
    }

    if (!\copy($item->getPathname(), $destination)) {
        throw new RuntimeException('Unable to copy: '.$item->getPathname());
    }

    ++$copied;
}

echo \sprintf("xBBCode assets copied: %d file(s) to %s\n", $copied, $target);
