#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$bootstrap = new App\Bootstrap;
$container = $bootstrap->bootWebApplication();
$application = $container->getByType(Contributte\Console\Application::class);

exit($application->run());



