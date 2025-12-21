<?php
declare(strict_types=1);
namespace App\Presentation\Product;

use Nette\Application\UI\Form;

use App\Presentation\Product\ProductFacade;

use App\Forms\ProductFormFactory;

use App\Database\Product;

class ProductPresenter extends \App\Presentation\BasePresenter {
    
    private ProductFacade $productFacade;
    
    private ?Product $product = null;
    
    private int $productsCnt;
    
    private ?string $productInternalID;
    private string $searchSlug = "";
    
    private int $pageNumber = 1;
    
    public function __construct(ProductFacade $productFacade) {
	parent::__construct();
	
	$this->productFacade = $productFacade;
    }

    public function actionDefault(){
	$this->productsCnt =  $this->productFacade->getProductsCount();
    }
    
    public function renderDefault(){
	$products =  $this->productFacade->getPaginatedProducts($this->pageNumber, $this->searchSlug);
	
	$this->template->products = $products;
	$this->template->totalProductsCount = $this->productsCnt;
	$this->template->searchedProductsCount = count($products);
	$this->template->page = $this->pageNumber;
	$this->template->limit = $this->productFacade->getPaginationLimit();
    }
    
    public function actionDetail($pid){
	$this->productInternalID = $pid;
	if(isset($this->productInternalID)){
	    $this->product = $this->productFacade->get(null, $this->productInternalID);
	    if(empty($this->product)){
		$this->flashMessage('Cannot find the product!');
		$this->redirect('Product:default'); 
	    }
	}
    }
    
    public function renderDetail(){
	$this->template->product = $this->product;
    }

    public function createComponentProductDetailForm(): Form{
	$form = new Form();

	ProductFormFactory::createProductForm($form,$this->product);
	
	if(empty($this->product)){
	    $form->addSubmit('submitProduct','Save');
	    $form->onSuccess[] = [$this,'productDetailFormSuccess'];
	} else {
	    $form->addSubmit('submitProduct','Edit');
	    $form->onSuccess[] = [$this,'productDetailUpdateFormSuccess'];
	    $form->addSubmit('deleteProduct', 'Delete')->onClick[] = [$this, 'deleteProduct'];
	}
	
	return $form;
    }
    
    public function productDetailFormSuccess(Form $form, $data){
	try {
	    $productInternalID = $this->productFacade->create($data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx) {
	    $this->flashMessage('Cannot create the product! Catalogue code has to be unique!');
	    $this->redirect('this');
	}
	
	if(empty($productInternalID)){
	    $this->flashMessage('Cannot create the product!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The product has been created!');
	$this->redirect('this',['pid' => $productInternalID]);
    }
    
    public function productDetailUpdateFormSuccess(Form $form, $data){
	try{
	    $productUpdated = $this->productFacade->update($form, $data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx) {
	    $this->flashMessage('Cannot create the product! Catalogue code has to be unique!');
	    $this->redirect('this');
	}
	
	if(!$productUpdated){
	    $this->flashMessage('Cannot update the product!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The product has been updated!');
	$this->redirect('this');
    }
    
    public function deleteProduct(){
	$productDeleted = $this->productFacade->delete($this->product);
	if(!$productDeleted){
	    $this->flashMessage('Cannot delete the product!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('The product has been deleted!');
	$this->redirect('Product:default');
    }
    
    public function handleGetProductsSearch($searchSlug){
	if(strlen($searchSlug) < 4 && !empty(strlen($searchSlug))){
	    $this->sendJson(false);
	}
	
	$this->searchSlug = $searchSlug;
	$this->redrawControl('products');
    }
    
    public function handleRedrawPageData($pageNumber, $searchSlug){
	$this->pageNumber = (int)$pageNumber;
	$this->searchSlug = $searchSlug;
	$this->redrawControl('products');
    }
}
