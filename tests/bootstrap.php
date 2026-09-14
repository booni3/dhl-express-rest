<?php

$root = dirname(__DIR__);
$specSources = glob($root.'/docs/dpdhl-express-api-*.yaml');

if (! $specSources) {
    throw new RuntimeException('No DHL OpenAPI spec found in docs/.');
}

natsort($specSources);
$specSource = end($specSources);
$specBuild = $root.'/build/spec.yaml';

passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/bin/build-spec.php'), $exitCode);

if ($exitCode !== 0 || ! file_exists($specBuild) || filemtime($specBuild) < filemtime($specSource)) {
    throw new RuntimeException('Failed to build sanitized DHL OpenAPI spec.');
}

require $root.'/vendor/autoload.php';
