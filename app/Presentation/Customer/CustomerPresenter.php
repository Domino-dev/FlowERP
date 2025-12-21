<?php
declare(strict_types=1);
namespace App\Presentation\Customer;

use Nette\Application\UI\Form;

use App\Presentation\Customer\CustomerFacade;

use App\Database\Customer;

class CustomerPresenter extends \App\Presentation\BasePresenter {
    
    private CustomerFacade $customerFacade;
    
    private ?Customer $customer = null;
    private ?\Doctrine\ORM\Tools\Pagination\Paginator $customers = null;
    private array $priceLists = [];
    
    private int $totalCustomersCnt;
    private int $pageNumber = 1;
    
    private ?string $customerInternalID = null;
    private string $customerIdentificator;
    private string $searchSlug = "";
    
    public function __construct(
	    CustomerFacade $customerFacade) {
	parent::__construct();
	$this->customerFacade = $customerFacade;
    }
    
    public function actionDefault(){
	// get a total customers count
	$this->totalCustomersCnt = $this->customerFacade->getCustomersCnt();
    }
    
    public function renderDefault(){
	$this->customers = $this->customerFacade->getPaginatedCustomers($this->pageNumber, $this->searchSlug);
	
	$this->template->customers = $this->customers;
	$this->template->page = $this->pageNumber;
	$this->template->limit = $this->customerFacade->getLimit();
	$this->template->customersTotalCount = $this->totalCustomersCnt;
	$this->template->searchedCustomersCount = count($this->customers);
    }
    
    public function actionDetail($cid){
	
	$this->customerInternalID = $cid;
	if(isset($this->customerInternalID)){
	    $this->customer = $this->customerFacade->getCustomerByInternalID($this->customerInternalID);
	    if(empty($this->customer )){
		$this->flashMessage('Cannot find the customer!');
		$this->redirect('Customer:default'); 
	    }
	    
	    $this->customerIdentificator = $this->customer->getIdentificator();
	} else {
	    $this->customerIdentificator = $this->customerFacade->generateCustomerIdentificator();
	}
	
	$this->priceLists = $this->customerFacade->getPriceLists();
    }
    
    public function renderDetail(): void{
	$this->template->customer = $this->customer;
    }
    
    public function createComponentCustomerDetailForm(): Form{
	$form = new Form();
	
	\App\Forms\CustomerFormFactory::createCustomerForm($form,$this->getPresenter()->getName(),$this->customerIdentificator,$this->customer,$this->priceLists);
	
	if(!empty($this->customer)){
	    $form->addSubmit('submitCustomer','Save edit');
	    $form->onSuccess[] = [$this,'customerDetailUpdateFormSuccess'];
	    $form->addSubmit('deleteCustomer', 'Delete')->onClick[] = [$this, 'removeCustomer'];
	} else {
	    $form->addSubmit('submitCustomer','Save');
	    $form->onSuccess[] = [$this,'customerDetailFormSuccess'];
	}
	
	$form->onError[] = $this->handleCustomerFormError(...);
	return $form;
    }
    
    public function handleCustomerFormError(Form $form){
	$form->addError('Something went wrong!');
    }
    
    public function customerDetailFormSuccess(Form $form, \stdClass $data){
	
	$customerInternalID = null;
	try{
	    $customerInternalID = $this->customerFacade->createCustomer($data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    $this->flashMessage('The identificator has to be unique!');
	    $this->redirect('this');
	}
	
	if(empty($customerInternalID)){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The customer has been created!');
	$this->redirect('this',['cid' => $customerInternalID]);
    }
    
    public function customerDetailUpdateFormSuccess(Form $form, \stdClass $data){
	
	$customerUpdated = false;
	try{
	    $customerUpdated = $this->customerFacade->updateCustomer($data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    $this->flashMessage('The identificator has to be unique!');
	    $this->redirect('this');
	}
	
	if(!$customerUpdated){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The customer has been updated!');
	$this->redirect('this');
    }
    
    public function removeCustomer(){
	if(empty($this->customerInternalID)){
	    $this->flashMessage('Wrong customer ID!');
	    $this->redirect('this'); 
	}
	
	$customerDeleted = $this->customerFacade->deleteCustomer($this->customer);
	if($customerDeleted){
	    $this->flashMessage('Customer has been removed!');
	    $this->redirect('Customer:default');
	}
	
	$this->flashMessage('Cannot remove the customer!');
	$this->redirect('this');
    }
    
    public function handleCheckIdentificatorUniqueness($identificator){
	if(empty($identificator)){
	    return false;
	}
	
	$isAlredyInUse = $this->customerFacade->checkIdentificatorUniqueness($identificator);
	$this->sendJson($isAlredyInUse);
    }
 
    public function handleCustomersSearch($searchSlug){
	if(strlen($searchSlug) < 4 && !empty(strlen($searchSlug))){
	    $this->sendJson(false);
	}
	
	$this->searchSlug = (string)$searchSlug;
	$this->redrawControl('customers');
    }
    
    public function handleRedrawPageData($pageNumber, $searchSlug){
	$this->pageNumber = (int)$pageNumber;
	$this->searchSlug = (string)$searchSlug;
	$this->redrawControl('customers');
    }
}
