<?php
declare(strict_types=1);
namespace App\Presentation\Company;

use Nette\Application\UI\Form;

use App\Presentation\BasePresenter;

use App\Presentation\BasePresenterFacade;
use App\Presentation\CompanyUser\CompanyFacade;

use App\Database\Company;

class CompanyPresenter extends BasePresenter{
    
    private CompanyFacade $companyFacade;
    
    private ?Company $company = null;
    
    private ?string $companyInternalID;
    
    public function __construct(
	    BasePresenterFacade $basePresenterFacade,
	    CompanyFacade $companyFacade) {
	parent::__construct($basePresenterFacade);
	$this->companyFacade = $companyFacade;
    }
    
    public function actionDefault($cid){
	if(!empty($cid)){
	    $this->company = $this->companyFacade->getCompanyData($cid);
	}
	
	$this->companyInternalID = $cid;
    }
    
    public function renderDefault(){
	
    }

    public function createComponentCompanyForm(): ?Form {
	$form = new Form();
	
	\App\Forms\CompanyFormFactory::createCompanyForm($form,$this->company);
	
	$form->addSubmit('submitCompany','Save');
	
	if(empty($this->company)){
	    $form->onSuccess[] = [$this,'saveCompanyData'];
	} else {
	    $form->onSuccess[] = [$this,'updateCompanyData'];
	}
	
	return $form;
    }
    
    public function saveCompanyData(Form $form, \stdClass $data): void{
	
	$companyInternalID = $this->companyFacade->createCompanyData($data);
	if(empty($companyInternalID)){
	    $this->flashMessage('Cannot create the company!','error');
	    $this->redirect('this');
	}
	
	$this->flashMessage('Company has been created!','success');
	$this->redirect('this',['cid' => $companyInternalID]);
    }
    
    public function updateCompanyData(Form $form, \stdClass $data): void{
	
	$companyUpdated = $this->companyFacade->updateCompanyData($this->company);
	
	if(!$companyUpdated){
	    $this->flashMessage('Cannot update the company!','error');
	    $this->redirect('this');
	}
	
	$this->flashMessage('Company has been updated!','success');
	$this->redirect('this',['cid' => $this->company->getInternalID()]);
    }
}
