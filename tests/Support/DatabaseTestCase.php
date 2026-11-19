<?php
declare(strict_types=1);
namespace Tests\Support;

use Nette\DI\Container;
use Nettrine\ORM\ManagerProvider;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Tester\Environment;
use Tester\TestCase;

/**
 * Base for tests running against the test database (tests/config.neon).
 * The schema is dropped and created again before every test method.
 */
abstract class DatabaseTestCase extends TestCase {

    protected Container $container;

    protected EntityManagerInterface $entityManagerInterface;

    protected function setUp(): void {
	// tests run in parallel, only one of them may use the database
	Environment::lock('database', TEMP_DIR);

	$this->container = createContainer();
	$this->entityManagerInterface = $this->container->getByType(ManagerProvider::class)->getDefaultManager();

	$this->createDatabase();

	$metadata = $this->entityManagerInterface->getMetadataFactory()->getAllMetadata();
	$schemaTool = new SchemaTool($this->entityManagerInterface);
	$schemaTool->dropDatabase();
	$schemaTool->createSchema($metadata);
    }

    /**
     * Re-reads entities from the database instead of the identity map.
     */
    protected function clear(): void {
	$this->entityManagerInterface->clear();
    }

    protected function persist(object ...$entities): void {
	foreach($entities as $entity){
	    $this->entityManagerInterface->persist($entity);
	}
	$this->entityManagerInterface->flush();
    }

    private function createDatabase(): void {
	$params = $this->entityManagerInterface->getConnection()->getParams();
	$dbName = $params['dbname'];
	unset($params['dbname']);

	try{
	    DriverManager::getConnection($params)->executeStatement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	} catch (\Doctrine\DBAL\Exception $ex){
	    Environment::skip('Database is not available: ' . $ex->getMessage());
	}
    }
}
