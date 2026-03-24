<?php
declare(strict_types=1);
namespace App\Presentation;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;

use App\Database\CompanyRepository;

use App\Database\Company;

/**
 * Description of BasePresenterFacade
 *
 * @author stepa
 */
class BasePresenterFacade { 
    
    private CompanyRepository $companyRepository;
    
    public function __construct(ManagerProvider $managerProvider) {
	$defaultManager = $managerProvider->getDefaultManager();
	$this->companyRepository = $defaultManager->getRepository(Company::class);
    }
    
    public function getCompanyInternalID():?string{
	$company = $this->companyRepository->findOneBy([]);
	
	return $company?->getInternalID();
    }
}
