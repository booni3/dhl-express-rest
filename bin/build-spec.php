<?php

$root = dirname(__DIR__);
$sources = glob($root.'/docs/dpdhl-express-api-*.yaml');

if (! $sources) {
    fwrite(STDERR, "No DHL OpenAPI spec found in docs/.\n");
    exit(1);
}

natsort($sources);
$source = end($sources);
$target = $argv[1] ?? $root.'/build/spec.yaml';
$targetDirectory = dirname($target);

if (! is_dir($targetDirectory)) {
    mkdir($targetDirectory, 0777, true);
}

$contents = file_get_contents($source);

if (! preg_match('//u', $contents)) {
    $contents = iconv('UTF-8', 'UTF-8//IGNORE', $contents);
}

$lines = preg_split('/\R/u', $contents);
$sanitized = array_map(static function (string $line): string {
    return rtrim(expandTabs($line, 2));
}, $lines);

file_put_contents($target, implode(PHP_EOL, $sanitized).PHP_EOL);

function expandTabs(string $line, int $tabSize): string
{
    $expanded = '';
    $column = 0;
    $length = strlen($line);

    for ($i = 0; $i < $length; $i++) {
        $char = $line[$i];

        if ($char === "\t") {
            $spaces = $tabSize - ($column % $tabSize);
            $expanded .= str_repeat(' ', $spaces);
            $column += $spaces;
            continue;
        }

        $expanded .= $char;
        $column++;
    }

    return $expanded;
}
