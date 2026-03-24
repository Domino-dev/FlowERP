<?php
declare(strict_types=1);
namespace App\Presentation\Invoice;

use Nette\Application\UI\Form;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Templates\InvoicePdfFactory;

use App\Presentation\BasePresenterFacade;
use App\Presentation\Invoice\InvoiceFacade;

use App\Database\Invoice;
use App\Database\InvoiceCustomer;

final class InvoicePresenter extends \App\Presentation\BasePresenter
{
    
    private InvoiceFacade $invoiceFacade;
    
    private InvoicePdfFactory $invoicePdfFactory;
    
    private ?Invoice $invoice = null;
    private ?InvoiceCustomer $invoiceCustomer = null;
    
    private ?Paginator $invoices;
    
    private ?bool $isPriceListWithVAT = false;
    private bool $editingInvoice = false;
    private ?string $priceListCurrencyISO = null;
    private ?string $priceListInternalID = null;
    private string $userInternalID;
    private ?string $invoiceInternalID;
    private array $priceListsForSelect = [];
    private array $paymentMethods = [];
    private ?string $customerIdentificator = null;
    private string $searchSlug = "";
    private int $invoiceTotalCount = 0;
    
    private int $pageNumber = 1;
    private array $statusCode = [];
    
    public function __construct(
	    BasePresenterFacade $basePresenterFacade,
	    InvoiceFacade $invoiceFacade, 
	    InvoicePdfFactory $invoicePdfFactory) {
	parent::__construct($basePresenterFacade);
	
	$this->invoiceFacade = $invoiceFacade;
	$this->invoicePdfFactory = $invoicePdfFactory;
    }
    
    public function actionDefault(): void{
	$this->invoiceTotalCount = $this->invoiceFacade->getInvoicesCount();
    }
    
    public function renderDefault(): void{
	
	$invoices = $this->invoiceFacade->getPaginatedInvoices($this->pageNumber,$this->searchSlug, $this->statusCode);
	$this->template->invoices = $invoices;
	$this->template->invoicesCnt = count($invoices);
	$this->template->invoicesTotalCnt = $this->invoiceTotalCount;
	$this->template->page = $this->invoiceFacade->getPage();
	$this->template->limit = $this->invoiceFacade->getLimit();
    }
    
    public function actionDetail($iid, $open): void{
	$this->userInternalID = $this->user->getId();
	
	$this->invoiceInternalID = $iid;
	
	$invoiceInternalID = $iid;
	if(!empty($invoiceInternalID)){
	    $this->invoice = $this->invoiceFacade->getInvoice($invoiceInternalID);
	    $this->priceListInternalID = $this->invoice?->getPriceList()->getInternalID();
	    $this->invoiceCustomer = $this->invoice?->getInvoiceCustomer();
	    $customer = $this->invoice?->getCustomer();
	    
	    $this->customerIdentificator = $customer->getIdentificator();
	}
	
	$this->editingInvoice = !empty($open) || empty($this->invoice);
	
	$this->priceListsForSelect = $this->invoiceFacade->getPriceLists();
	
	foreach($this->priceListsForSelect as $priceList){
	    if(empty($this->priceListInternalID) && $priceList->getIsDefault()){
		$this->priceListCurrencyISO = $priceList->getCurrency();
		$this->isPriceListWithVAT = $priceList->getIsWithVAT();
		break;
	    } else if(!empty($this->priceListInternalID) && $this->priceListInternalID === $priceList->getInternalID()){
		$this->priceListCurrencyISO = $priceList->getCurrency();
		$this->isPriceListWithVAT = $priceList->getIsWithVAT();
		break;
	    }
	}
    }
    
    public function actionPdf(string $iid): void{
	$invoice = $this->invoiceFacade->getInvoice($iid);
	if(empty($invoice)){
	    return ;
	}
	
	$company = $this->invoiceFacade->getCompany();
	
	$pdf = $this->invoicePdfFactory->createInvoice($invoice,$company);

	$this->getHttpResponse()->setContentType('application/pdf');
	echo $pdf->Output('S');

	$this->terminate();
    }
    
    public function renderDetail($iid, $open): void{
	$this->template->invoiceInternalID = $this->invoiceInternalID;
	$this->template->invoice = $this->invoice;
	$this->template->editingInvoice = $this->editingInvoice;
	$this->template->priceListCurrencyISO = $this->priceListCurrencyISO;
	$this->template->priceListWithVat = $this->isPriceListWithVAT;
	$this->template->customer = null;
	$this->template->invoiceTotalPriceWithoutVAT = isset($this->invoice) ? $this->invoice->getTotal() : 0;
	$this->template->invoiceTotalPriceWithVAT = isset($this->invoice) ? $this->invoice->getTotalWithVAT() : 0;
    }
    
    public function createComponentInvoiceForm(): Form{
	$form = new Form();
	
	$readOnly = !$this->editingInvoice;
	
	\App\Forms\InvoiceFormFactory::createInvoiceForm($form,$readOnly,$this->getPresenter(), $this->invoice, $this->paymentMethods,$this->priceListsForSelect, null, $this->priceListInternalID);
	\App\Forms\CustomerFormFactory::createCustomerForm($form,$readOnly,$this->getPresenter()->getName(),$this->customerIdentificator,$this->invoiceCustomer, $this->priceListsForSelect, false);
	
	if(empty($this->invoice)){
	    $form->addSubmit('submitInvoice', 'Save invoice');
	    $form->onSuccess[] = [$this,'invoiceFormSuccess'];
	} else if($this->editingInvoice){
	    $form->addSubmit('editInvoice', 'Save invoice');
	    $form->onSuccess[] = [$this,'updateInvoiceFormSuccess'];
	    $form->addSubmit('deleteInvoice', 'Cancel edits')->setHtmlAttribute('id','delete-invoice')->onClick[] = [$this, 'cancelInvoice'];
	} else if(!$this->editingInvoice){
	    $form->addSubmit('deleteInvoice', 'Delete Invoice')->setHtmlAttribute('id','delete-invoice')->onClick[] = [$this, 'deleteInvoice'];
	}
	
	$form->onError[] = function ($form) {
	    foreach ($form->getControls() as $control) {
		if ($control->getErrors()) {
		    bdump($control->getName(), 'Field name');
		    bdump($control->getErrors(), 'Errors');
		}
	    }
	};
	
	return $form;
    }
    
    public function invoiceFormSuccess(Form $form, \stdClass $data){
	try{
	    $invoiceID = $this->invoiceFacade->createInvoice($data,$this->userInternalID);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    // LOG
	    throw $ex;
	}
	
	if(empty($invoiceID)){
	    $this->flashMessage('Cannot create the invoice!');
	    $this->redirect('this');
	}
	
	$this->flashMessage('Invoice has been successfully created!');
	$this->redirect('Invoice:detail',['iid' => $invoiceID]);
    }
    
    public function updateInvoiceFormSuccess(Form $form, \stdClass $data){
	try{
	    $invoiceUpdated = $this->invoiceFacade->updateInvoice($data);
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    // LOG
	    throw $ex;
	}
	
	if($invoiceUpdated){
	    $this->flashMessage('Invoice has been successfully updated!');
	    $this->redirect('this');
	}
	
	$invoiceInternalID = $data->invoiceInternalID;
	
	$this->flashMessage('Cannot update the invoice!');
	$this->redirect('this',['iid' => $invoiceInternalID, 'open' => false]);
    }
    
    public function handleGetCustomerAutocompleteSuggestion($slug){
	
	if(empty($slug) || strlen($slug) < 3){
	    $this->sendJson(false);
	}
	
	$this->sendJson($this->invoiceFacade->getCustomerAutocomplete($slug));
    }
    
    public function handleGetCustomerData($customerInternalID){
	if(empty($customerInternalID)){
	    $this->sendJson(false);
	}
	
	$this->sendJson($this->invoiceFacade->getCustomerData($customerInternalID,true));
    }
    
    public function handleGetProductAutocompleteSuggestion($slug){
	
	if(empty($slug) || strlen($slug) < 3){
	    $this->sendJson(false);
	}
	
	$this->sendJson($this->invoiceFacade->getProductAutocomplete($slug));
    }
    
    public function handleGetProductData($productInternalID, $priceListInternalID){
	if(empty($productInternalID)){
	    $this->sendJson(false);
	}
	
	$this->sendJson($this->invoiceFacade->getProductArrayData($productInternalID,$priceListInternalID));
    }
    
    public function handleGetPrices($priceListInternalID, $productInternalIDsJSON, $productCatalogueCodesJSON) {

	if (empty($priceListInternalID) || (empty($productInternalIDsJSON) && empty($productCatalogueCodesJSON))) {
	    $this->sendJson(false);
	}

	$productInternalIDs = json_decode($productInternalIDsJSON, true);
	$productCatalogueCodes = json_decode($productCatalogueCodesJSON, true);
	$pricesValueByProductInternal = $this->invoiceFacade->getPrices($priceListInternalID, $productInternalIDs, $productCatalogueCodes);
	
	$this->sendJson($pricesValueByProductInternal);
    }

    public function handleGetPriceListData($priceListInternalID){
	
	if(empty($priceListInternalID)){
	    $this->sendJson(false);
	}
	
	$this->sendJson($this->invoiceFacade->getPriceListByInternalID($priceListInternalID,true));
    }
    
    public function cancelInvoice(){
	$this->redirect('Invoice:detail',['iid' => $this->invoiceInternalID]);
    }
    
    public function deleteInvoice(){
	$companyUserDeleted = $this->invoiceFacade->deleteInvoice($this->invoice);
	if(!$companyUserDeleted){
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('Invoice:default');
	}
	
	$this->flashMessage('Invoice has been successfully deleted!');
	$this->redirect('Invoice:default');
    }
    
    public function handleFilterInvoices($pageNumber,$searchSlug){
	$statusCode = $this->getHttpRequest()->getPost('statusCode');
	
	if(!empty($searchSlug) && strlen($searchSlug) < 4 && empty($statusCode)){
	    $this->sendJson(false);
	}
	
	bdump($statusCode);
	
	$this->pageNumber = (int)$pageNumber ?? 1;
	$this->searchSlug = $searchSlug ?? "";
	$this->statusCode = (array)$statusCode;
	$this->redrawControl('invoices');
    }
}