<?php
declare(strict_types=1);
namespace App\Presentation\CompanyUser;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Application\UI\Form;

use App\Services\CompanyUserService;

use App\Database\CompanyUserRepository;

use App\Database\CompanyUser;

class CompanyUserFacade {
    
    CONST PAGE = 1;
    CONST LIMIT = 10;
    
    private CompanyUserService $companyUserService;
    
    private ManagerProvider $managerProvider;
    private EntityManagerInterface $entityManagerInterface;
    
    private CompanyUserRepository $companyUserRepository;
    
    public function __construct(ManagerProvider $managerProvider, CompanyUserService $companyUserService) {
	$this->managerProvider = $managerProvider;
	$this->entityManagerInterface = $this->managerProvider->getDefaultManager();
	$this->companyUserRepository = $this->entityManagerInterface->getRepository(CompanyUser::class);
	$this->companyUserService = $companyUserService;
    }
    
    public function getLimit():int{
	return $this->companyUserRepository->getPaginatedLimit();
    }
    
    public function getCompanyUsersCount():?int{
	try{
	    return $this->companyUserRepository->count();
	} catch (\Doctrine\DBAL\Exception $ex) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $ex){
	    // LOG
	}
	
	return 0;
    }
    
    public function getPaginatedCompanyUsers(int $page,string $searchSlug):?Paginator{
	try{
	    return $this->companyUserRepository->findPaginated($page,$searchSlug);
	} catch (\Doctrine\DBAL\Exception $ex) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $ex){
	    // LOG
	}
	
	return null;
    }
    
    public function getCompanyUser(?int $companyUserID, ?string $companyUserInternalID = null):?CompanyUser{
	
	if(empty($companyUserID) && !empty($companyUserInternalID)){
	    $companyUser = $this->companyUserRepository->findOneBy(['internalID' => $companyUserInternalID]);
	    
	    if(!empty($companyUser)){
		$companyUserID = $companyUser->getId();
	    }
	}
	
	if(!empty($companyUserID)){
	    return $this->companyUserRepository->find($companyUserID);
	}
	
	return null;
    }
    
    public function createCompanyUser(Form $form, \stdClass $companyUserDataRaw):?string{
	
	$roles = $form->getComponent('roles')->items;
	
	$companyUser = $this->companyUserService->prepareUser($companyUserDataRaw,$roles);
	
	try{
	    $this->entityManagerInterface->persist($companyUser);
	    $this->entityManagerInterface->flush();
	    return $companyUser->getInternalID();
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
    
    public function updateCompanyUser(Form $form, \stdClass $companyUserDataRaw):bool{

	$companyUserInternalID = $companyUserDataRaw->internalID;
	if(empty($companyUserInternalID)){
	    return false;
	}
	
	/** @var CompanyUser $companyUser */
	$companyUser = $this->companyUserRepository->findOneBy(['internalID' => $companyUserInternalID]);
	if(empty($companyUser)){
	    return false;
	}
	
	$companyUser->setName($companyUserDataRaw->name);
	$companyUser->setEmail($companyUserDataRaw->email);
	$companyUser->setPhone((string)$companyUserDataRaw->phone);
	
	$roles = $form->getComponent('roles')->items;
	$role = $roles[$companyUserDataRaw->roles];
	$companyUser->setRole($role);
	
	if(!empty($companyUserDataRaw->password)){
	    $hashedPassword = $this->companyUserService->hashPassword($companyUserDataRaw->password);
	    $companyUser->setPassword($hashedPassword);
	}
	
	$companyUser->setNote($companyUserDataRaw->note);
	$companyUser->setIsEnabled($companyUserDataRaw->isEnabled);
	
	try{
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    // LOG
	} catch (\Doctrine\DBAL\Exception $ex) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $ex){
	    // LOG
	}
	
	return false;
    }
    
    public function deleteCompanyUser(CompanyUser $companyUser):bool{
	if(empty($companyUser)){
	    return false;
	}
	
	if($companyUser->getIsMain()){
	    return false;
	}
	
	try {
	    $this->entityManagerInterface->remove($companyUser);
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    // LOG
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
