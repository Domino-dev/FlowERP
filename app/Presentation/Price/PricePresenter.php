<?php
declare(strict_types=1);
namespace App\Presentation\Price;

use Nette\Application\UI\Form;

use App\Presentation\Price\PriceFacade;

use App\Forms\PriceFormFactory;

use App\Database\Price;
use App\Database\Product;

class PricePresenter extends \App\Presentation\BasePresenter {
    
    private PriceFacade $priceFacade;
    
    private ?Product $product = null;
    private array $prices = [];
    
    private ?string $productInternalID;
    private ?string $priceListInternalID = null;
    private string $searchSlug = "";

    private array $priceListsForSelect = [];
    
    private int $pageNumber = 1;
    private int $totalPricesCount = 0;
    
    public function __construct(PriceFacade $priceFacade) {
	parent::__construct();
	
	$this->priceFacade = $priceFacade;
    }
    
    public function actionDefault(){
	$this->totalPricesCount = $this->priceFacade->getPricesCount();
    }

    public function renderDefault(){
	$prices = $this->priceFacade->getPaginatedPrices($this->pageNumber,$this->searchSlug);

	$this->template->prices = $prices;
	$this->template->totalPricesCount = $this->totalPricesCount;
	$this->template->searchedPricesCount = count($prices);
	$this->template->page = $this->pageNumber;
	$this->template->limit = $this->priceFacade->getLimit();
    }
    
    public function actionDetail($pid){
	$this->productInternalID = $pid;
	
	if(isset($this->productInternalID )){
	    $this->prices = $this->priceFacade->getPricesByProduct($this->productInternalID);
	    if(empty($this->prices)){
		$this->flashMessage('Cannot find the price!');
		$this->redirect('Price:default'); 
	    }
	    
	    $this->product = $this->priceFacade->getProductByInternalID($this->productInternalID);
	}
	
	$this->priceListsForSelect = $this->priceFacade->getPriceListsForSelect();
    }
    
    public function renderDetail(){
	$this->template->prices = $this->prices;
	$this->template->product = $this->product;
	$this->template->priceListsForSelect = $this->priceListsForSelect;
    }

    public function createComponentPriceDetailForm(): Form{
	$form = new Form();

	PriceFormFactory::createPriceForm($form,$this->getPresenter(),$this->prices, $this->product,$this->priceListsForSelect, $this->priceListInternalID);
	
	if(!empty($this->prices)){
	    $form->addSubmit('submitPrice','Edit')->setHtmlAttribute('id','submit-price');
	    $form->onSuccess[] = [$this,'priceDetailUpdateFormSuccess'];
	    $form->addSubmit('deletePrice', 'Delete')->setHtmlAttribute('id','delete-price')->onClick[] = [$this, 'deletePrice'];
	} else {
	    $form->addSubmit('submitPrice','Save')->setHtmlAttribute('id','submit-price');
	    $form->onSuccess[] = [$this,'priceDetailFormSuccess'];
	}
	
	$form->onError[] = function ($form){
	    dump($form->getErrors());
	};
	return $form;
    }
    
    public function priceDetailFormSuccess(Form $form, $data){
	$priceInternalID = null;
	try{
	    $priceInternalID = $this->priceFacade->create($data, $form);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
	    // LOG
	    $this->flashMessage('Price with this price list and validation already exists!');
	    $this->redirect('this');
	}
	
	if(empty($priceInternalID)){
	    $this->flashMessage('Cannot create the price!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The price has been created!');
	$this->redirect('this',['pid' => $priceInternalID]);
    }
    
    public function priceDetailUpdateFormSuccess(Form $form, $data){
    
	$priceUpdated = false;
	try{
	    $priceUpdated = $this->priceFacade->update($form, $data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
	    // LOG
	    $this->flashMessage('Price with this price list and validation already exists!');
	    $this->redirect('this');
	}
	
	if(!$priceUpdated){
	    $this->flashMessage('Cannot update the price!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The price has been updated!');
	$this->redirect('this');
    }
    
    public function deletePrice(){
	
	$priceDeleted = $this->priceFacade->delete($this->prices);
	
	if(!$priceDeleted){
	    $this->flashMessage('Cannot delete the price!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The price has been deleted!');
	$this->redirect('Price:default');
    }
    
    public function handleGetProductAutocomplete(string $slug){
	if(empty($slug)){
	   $this->sendJson(''); 
	}
	
	$this->sendJson($this->priceFacade->getProductAutocomplete($slug) ?? '');
    }
    
    public function handleGetProductPricesData(string $productInternalID){
	if(empty($productInternalID)){
	    $this->sendJson(false);
	}
	
	$this->prices = $this->priceFacade->getPricesByProduct($productInternalID);
	bdump($this->prices);
	if(empty($this->prices)){
	    $this->sendJson(false);
	}
	
	$this->redrawControl('productPrices');
    }
    
    public function handleGetPriceListData(string $priceListInternalID){
	if(empty($priceListInternalID)){
	   $this->sendJson(''); 
	}
	
	$priceList = $this->priceFacade->getPriceListByInternalID($priceListInternalID);
	if(empty($priceList) || !($priceList instanceof \App\Database\PriceList)) {
	    $this->sendJson('');
	}
	
	$this->sendJson($priceList->toArray());
    }
    
    public function handleGetPricesSearch($searchSlug){
	if(strlen($searchSlug) < 4 && !empty(strlen($searchSlug))){
	    $this->sendJson(false);
	}
	
	$this->searchSlug = $searchSlug;
	$this->redrawControl('prices');
    }
    
    public function handleRedrawPageData($pageNumber, $searchSlug){
	$this->pageNumber = (int)$pageNumber;
	$this->searchSlug = (string)$searchSlug;
	$this->redrawControl('prices');
    }
}
