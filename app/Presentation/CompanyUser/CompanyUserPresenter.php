<?php
declare(strict_types=1);
namespace App\Presentation\CompanyUser;

use Nette\Application\UI\Form;

use App\Presentation\BasePresenter;

use App\Presentation\CompanyUser\CompanyUserFacade;

use App\Database\CompanyUser;

class CompanyUserPresenter extends BasePresenter{
    
    private ?string $companyUserInternalID = null;
    private ?CompanyUser $companyUser = null;
    private int $companyUsersCount = 0;
    private CompanyUserFacade $companyUserFacade;
    
    private string $searchSlug = "";
    
    private int $pageNumber = 1;
    
    public function __construct(CompanyUserFacade $companyUserFacade) {
	$this->companyUserFacade = $companyUserFacade;
    }
    
    public function actionDetail($uid){
	$this->companyUserInternalID = $uid;
	if(isset($this->companyUserInternalID)){
	    $this->companyUser = $this->companyUserFacade->getCompanyUser(null, $this->companyUserInternalID);
	    if(empty($this->companyUser)){
		$this->flashMessage('Cannot find the company user!');
		$this->redirect('CompanyUser:default'); 
	    }
	}
    }
    
    public function actionDefault(){
	$this->companyUsersCount = $this->companyUserFacade->getCompanyUsersCount();
    }
    
    public function renderDefault(){
	$companyUsers = $this->companyUserFacade->getPaginatedCompanyUsers($this->pageNumber,$this->searchSlug);
	
	$this->template->companyUsers = $companyUsers;
	$this->template->page = $this->pageNumber;
	$this->template->limit = $this->companyUserFacade->getLimit();
	$this->template->searchedCompanyUsersCount = count($companyUsers);
	$this->template->totalCompanyUsersCount = $this->companyUsersCount;
    }
    
    public function renderDetail(){
	$this->template->companyUser = $this->companyUser;
    }
    
    public function createComponentUserDetailForm():Form{
	$form = new Form();
	
	\App\Forms\CompanyUserFormFactory::createCompanyUserForm($form, $this->companyUser);
	
	if(!empty($this->companyUser)){
	    $form->onSuccess[] = [$this,'companyUserDetailUpdateFormSuccess'];
	    $form->addSubmit('submitCompanyUser','Edit');
	    $form->addSubmit('deleteCompanyUser', 'Delete')->onClick[] = [$this, 'deleteCompanyUser'];
	} else {
	    $form->addSubmit('submitCompanyUser','Save');
	    $form->onSuccess[] = [$this,'companyUserDetailCreateFormSuccess'];
	}
	
	$form->onSuccess[] = [$this,'createCompanyUserSuccess'];
	
	return $form;
    }
    
    public function companyUserDetailCreateFormSuccess(Form $form, \stdClass $data){
	
	try{
	    $companyUserInternalID = $this->companyUserFacade->createCompanyUser($form, $data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
	    $this->flashMessage('The company companyUser with this e-mail already exists!');
	    $this->redirect('CompanyUser:default');
	}
	
	if(!empty($companyUserInternalID)){
	    $this->flashMessage('The company user has been successfully created!');
	    $this->redirect('this',['uid' => $companyUserInternalID]);
	}
	
	$this->flashMessage('Something went wrong!');
    }
    
    public function companyUserDetailUpdateFormSuccess(Form $form, \stdClass $data){
	try{
	    $isUpdated = $this->companyUserFacade->updateCompanyUser($form, $data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
	    $this->flashMessage('The company user with this e-mail already exists!');
	    $this->redirect('this');
	}
	
	if(!$isUpdated){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The company user has been successfully updated!');
	$this->redirect('this');
    }
    
    public function deleteCompanyUser(){
	$companyUserDeleted = $this->companyUserFacade->deleteCompanyUser($this->companyUser);
	
	if(!$companyUserDeleted){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('CompanyUser:default');
	}
	
	$this->flashMessage('The company user has been successfully updated!');
    }
    
    public function handleGetCompanyUsersSearch($searchSlug){
	if(strlen($searchSlug) < 4 && !empty(strlen($searchSlug))){
	    $this->sendJson(false);
	}
	
	$this->searchSlug = $searchSlug;
	$this->redrawControl('company-users');
    }
    
    public function handleRedrawPageData($pageNumber, $searchSlug){
	$this->pageNumber = $pageNumber;
	$this->searchSlug = $searchSlug;
	$this->redrawControl('company-users');
    }
}
