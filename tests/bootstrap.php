<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();
date_default_timezone_set('Europe/Prague');

define('ROOT_DIR', dirname(__DIR__));
define('TEMP_DIR', __DIR__ . '/temp');
Nette\Utils\FileSystem::createDir(TEMP_DIR);

require_once __DIR__ . '/Support/TestData.php';
require_once __DIR__ . '/Support/DatabaseTestCase.php';

/**
 * Creates the app DI container with test overrides (tests/config.neon).
 */
function createContainer(): Nette\DI\Container {
    $configDir = ROOT_DIR . '/config';

    $configurator = new Nette\Bootstrap\Configurator;
    $configurator->setDebugMode(false);
    $configurator->setTempDirectory(TEMP_DIR);
    $configurator->addStaticParameters([
	'appDir' => ROOT_DIR . '/app',
	'wwwDir' => ROOT_DIR . '/www',
    ]);

    $configurator->createRobotLoader()
	    ->addDirectory(ROOT_DIR . '/app')
	    ->register();

    $configurator->addConfig($configDir . '/common.neon');
    $configurator->addConfig($configDir . '/development.neon');
    $configurator->addConfig($configDir . '/services.neon');
    if(is_file($configDir . '/local.neon')){
	$configurator->addConfig($configDir . '/local.neon');
    }
    $configurator->addConfig(__DIR__ . '/config.neon');

    return $configurator->createContainer();
}
