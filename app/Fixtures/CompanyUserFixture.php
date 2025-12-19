<?php
declare(strict_types=1);
namespace App\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

use App\Database\CompanyUser;

class CompanyUserFixture implements FixtureInterface {

    /**
     * Load data fixtures with the passed ObjectManager
     */
    public function load(ObjectManager $manager): void {
	// password: tester123
	$companyUser = new CompanyUser(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		'Admin',
		NULL,
		'999999999',
		'admin@admin.cz',
		'$2y$10$yfaDtt/tUgXo.CsfO9zNMOiMVhzKyX6k/Ac8gEgUNYCXYXi9YU8p2',
		'admin',
		true,
		true);
	$manager->persist($companyUser);
	$manager->flush();
    }
}
