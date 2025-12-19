<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\UUIDGenerator;
use Nette\Security\Passwords;

use App\Database\CompanyUser;

/**
 * Description of UserService
 *
 * @author stepa
 */
class CompanyUserService {
    
    private Passwords $passwords;
    
    public function __construct(Passwords $passwords) {
	$this->passwords = $passwords;
    }
    
    public function prepareUser(\stdClass $userDataRaw, array $roles): ?CompanyUser{
	$role = $roles[$userDataRaw->roles];
	
	return new CompanyUser(
		UUIDGenerator::generateInternalID(), 
		$userDataRaw->name, 
		$userDataRaw->note, 
		(string)$userDataRaw->phone, 
		$userDataRaw->email, 
		$this->passwords->hash($userDataRaw->password), 
		$role, 
		true);
    }
    
    public function hashPassword(string $password):?string{
	return $this->passwords->hash($password);
    }
}
