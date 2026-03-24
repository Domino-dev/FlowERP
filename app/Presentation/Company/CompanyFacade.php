<?php
declare(strict_types=1);
namespace App\Presentation\CompanyUser;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Application\UI\Form;

use App\Services\CompanyService;

use App\Database\CompanyRepository;

use App\Database\Company;

class CompanyFacade {
    
    private ManagerProvider $managerProvider;
    private EntityManagerInterface $entityManagerInterface;
    
    private CompanyService $companyService;
    
    private CompanyRepository $companyRepository;
    
    public function __construct(
	    ManagerProvider $managerProvider, 
	    CompanyService $companyService) {
	$this->managerProvider = $managerProvider;
	$this->entityManagerInterface = $this->managerProvider->getDefaultManager();
	$this->companyRepository = $this->entityManagerInterface->getRepository(Company::class);
	$this->companyService = $companyService;
    }
    
    public function createCompanyData(\stdClass $companyDataRaw):?string{
	$company = $this->companyService->prepareCompany($companyDataRaw);
	
	try{
	    $this->entityManagerInterface->persist($company);
	    $this->entityManagerInterface->flush();
	    return $company->getInternalID();
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    throw $ex;
	} catch (\Doctrine\DBAL\Exception $ex) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $ex){
	    // LOG
	}
	
	return null;
    }
    
    public function getCompanyData(string $companyInternalID):?Company{
	return $this->companyRepository->findOneBy(['internalID' => $companyInternalID]);
    }
    
    public function updateCompanyData(Company $company, \stdClass $companyDataRaw): bool{
	
	$company->setBankNumber($companyDataRaw->bankNumber);
	$company->setCompanyNumber($companyDataRaw->companyNumber);
	$company->setVatNumber($companyDataRaw->vatNumber);
	
	$company->setEmail($companyDataRaw->email);
	$company->setName($companyDataRaw->name);
	$company->setPhone($companyDataRaw->phone);
	$company->setStreet($companyDataRaw->street);
	$company->setZip($companyDataRaw->zip);
	$company->setCity($companyDataRaw->city);
	
	$company->setCountryISO($companyDataRaw->country);
	$company->setWebDomain();
	
	try{
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    // LOG
	    throw $ex;
	} catch (\Doctrine\DBAL\Exception $ex) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $ex){
	    // LOG
	}
	
	return false;
    }
    
}
