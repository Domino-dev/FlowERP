<?php
/**
 * Test: user authentication against the test database.
 */
declare(strict_types=1);

use Nette\Security\AuthenticationException;
use Tester\Assert;

use App\Security\Authenticator;

use App\Database\CompanyUser;

use Tests\Support\DatabaseTestCase;
use Tests\Support\TestData;

require __DIR__ . '/../bootstrap.php';

class AuthenticatorTest extends DatabaseTestCase {

    private Authenticator $authenticator;

    private CompanyUser $companyUser;

    protected function setUp(): void {
	parent::setUp();

	$this->authenticator = $this->container->getByType(Authenticator::class);

	$this->companyUser = TestData::createCompanyUser();
	$this->persist($this->companyUser);
    }

    public function testValidCredentials(): void {
	$identity = $this->authenticator->authenticate(TestData::USER_EMAIL, TestData::USER_PASSWORD);

	Assert::same($this->companyUser->getInternalID(), $identity->getId());
	Assert::same(['admin'], $identity->getRoles());
    }

    public function testWrongPassword(): void {
	Assert::exception(
	    fn() => $this->authenticator->authenticate(TestData::USER_EMAIL, 'wrong'),
	    AuthenticationException::class
	);
    }

    public function testUnknownEmail(): void {
	Assert::exception(
	    fn() => $this->authenticator->authenticate('nobody@test.cz', TestData::USER_PASSWORD),
	    AuthenticationException::class
	);
    }

    public function testInvalidEmailFormat(): void {
	Assert::exception(
	    fn() => $this->authenticator->authenticate('not-an-email', TestData::USER_PASSWORD),
	    AuthenticationException::class
	);
    }

    public function testDisabledUserCannotSignIn(): void {
	$this->companyUser->setIsEnabled(false);
	$this->entityManagerInterface->flush();

	Assert::exception(
	    fn() => $this->authenticator->authenticate(TestData::USER_EMAIL, TestData::USER_PASSWORD),
	    AuthenticationException::class
	);
    }
}

(new AuthenticatorTest)->run();
