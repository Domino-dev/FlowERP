<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\UUIDGenerator;

use App\Database\Company;

/**
 * Description of UserService
 *
 * @author stepa
 */
class CompanyService {
    
    public function __construct() {
	
    }
    
    public function prepareCompany(\stdClass $userDataRaw): ?Company{
	
	return new Company(
		UUIDGenerator::generateInternalID(), 
		$userDataRaw->name, 
		$userDataRaw->phone, 
		$userDataRaw->email, 
		$userDataRaw->webDomain, 
		$userDataRaw->companyNumber, 
		$userDataRaw->vatNumber, 
		$userDataRaw->bankNumber, 
		$userDataRaw->street, 
		$userDataRaw->city, 
		$userDataRaw->zip, 
		$userDataRaw->country);
    }
}
