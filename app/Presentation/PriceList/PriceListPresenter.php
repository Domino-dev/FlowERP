<?php
declare(strict_types=1);
namespace App\Presentation\PriceList;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Application\UI\Form;

use App\Presentation\BasePresenter;

use App\Presentation\PriceList\PriceListFacade;

use App\Database\PriceList;

class PriceListPresenter extends BasePresenter{
    
    private ?PriceList $priceList = null;
    private PriceListFacade $priceListFacade;
    
    private ?string $priceListInternalID = null;
    private string $searchSlug = "";
    
    private int $pageNumber = 1;
    private int $totalPriceListsCount = 0;
    
    private ?Paginator $priceLists = null;
    
    public function __construct(PriceListFacade $priceListFacade) {
	$this->priceListFacade = $priceListFacade;
    }
    
    public function actionDefault(){
	$this->totalPriceListsCount = $this->priceListFacade->getTotalPriceListsCount();
    }
    
    public function renderDefault(){
	$this->priceLists = $this->priceListFacade->getPaginatedCustomers($this->pageNumber,$this->searchSlug);
	
	$this->template->priceLists = $this->priceLists;
	$this->template->page = $this->pageNumber;
	$this->template->limit = $this->priceListFacade->getPaginatedPriceListsLimit();
	$this->template->searchedPriceListsCount = count($this->priceLists);
	$this->template->totalPriceListsCount = $this->totalPriceListsCount;
    }
    
    public function actionDetail($plid){
	$this->priceListInternalID = $plid;
	if(isset($this->priceListInternalID)){
	    $this->priceList = $this->priceListFacade->get(null, $this->priceListInternalID);
	    if(empty($this->priceList)){
		$this->flashMessage('Cannot find the price list!');
		$this->redirect('PriceList:default'); 
	    }
	}
    }
    
    public function renderDetail(){
	$this->template->priceList = $this->priceList;
    }
    
    public function createComponentPriceListDetailForm():Form{
	$form = new Form();
	
	\App\Forms\PriceListFormFactory::createPriceListForm($form, $this->priceList);
	
	if(!empty($this->priceList)){
	    $form->addSubmit('submitPriceList','Edit');
	    $form->onSuccess[] = [$this,'priceListDetailUpdateFormSuccess'];
	    $form->addSubmit('deletePriceList', 'Delete')->onClick[] = [$this, 'deletePriceList'];
	} else {
	    $form->addSubmit('submitPriceList','Save');
	    $form->onSuccess[] = [$this,'priceListDetailCreateFormSuccess'];
	}
	
	return $form;
    }
    
    public function priceListDetailCreateFormSuccess(Form $form, \stdClass $data){
	
	$priceListInternalID = null;
	try{
	    $priceListInternalID = $this->priceListFacade->createPriceList($form, $data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx){
	    $this->flashMessage('This price list name already exists!');
	    $this->redirect('PriceList:default');
	}
	
	if(empty($priceListInternalID)){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The price list has been successfully created!');
	$this->redirect('this',['plid' => $priceListInternalID]);
    }
    
    public function priceListDetailUpdateFormSuccess(Form $form, \stdClass $data){
	
	$isUpdated = false;
	try{
	    $isUpdated = $this->priceListFacade->updatePriceList($form, $data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx){
	    $this->flashMessage('This price list name already exists!');
	    $this->redirect('PriceList:default');
	}
	
	if(!$isUpdated){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The price list has been successfully updated!');
    }
    
    public function deletePriceList(){
	
	$priceListDeleted = false;
	if(!empty($this->priceList)){
	    $priceListDeleted = $this->priceListFacade->deletePriceList($this->priceList);
	}
	
	if(!$priceListDeleted){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The price list has been successfully deleted!');
	$this->redirect('PriceList:default');
    }
    
    public function handleGetPriceListsSearch($searchSlug){
	if(strlen($searchSlug) < 3 && !empty($searchSlug)){
	    $this->sendJson(false);
	}
	
	
	$this->searchSlug = $searchSlug;
	$this->redrawControl('price-lists');
    }
    
    public function handleRedrawPageData($pageNumber, $searchSlug){
	$this->pageNumber = (int)$pageNumber;
	$this->searchSlug = $searchSlug;
	$this->redrawControl('price-lists');
    }
}
