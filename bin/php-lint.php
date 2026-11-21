<?php

declare(strict_types=1);

$file = $argv[1] ?? '';

if ('' === $file || !\is_file($file) || !\is_readable($file)) {
    \fwrite(\STDOUT, 'Could not open input file: '.(string) $file."\n");
    exit(1);
}

$source = \file_get_contents($file);
if (false === $source) {
    \fwrite(\STDOUT, 'Could not read input file: '.$file."\n");
    exit(1);
}

try {
    $tokens = \token_get_all($source, \TOKEN_PARSE);
} catch (ParseError $exception) {
    \fwrite(\STDOUT, \sprintf(
        "Parse error: %s in %s on line %d\n",
        $exception->getMessage(),
        $file,
        $exception->getLine(),
    ));
    exit(255);
}

\fwrite(\STDOUT, 'No syntax errors detected in '.$file."\n");
exit(0);
