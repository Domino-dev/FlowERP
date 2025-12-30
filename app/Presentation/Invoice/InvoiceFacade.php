<?php
declare(strict_types=1);
namespace App\Presentation\Invoice;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Database\InvoiceRepository;
use App\Database\InvoiceItemRepository;
use App\Database\PriceListRepository;
use App\Database\CustomerRepository;
use App\Database\InvoiceCustomerRepository;
use App\Database\InvoiceCustomerBillingAddressRepository;
use App\Database\InvoiceCustomerDeliveryAddressRepository;
use App\Database\ProductRepository;
use App\Database\CompanyUserRepository;
use App\Database\PriceRepository;

use App\Services\ProductService;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\InvoiceItemService;

use App\Database\Invoice;
use App\Database\InvoiceItem;
use App\Database\PriceList;
use App\Database\Customer;
use App\Database\InvoiceCustomer;
use App\Database\Product;
use App\Database\CompanyUser;
use App\Database\Price;

/**
 * Description of InvoiceFacade
 *
 * @author stepa
 */
class InvoiceFacade{
    
    CONST PAGE = 1;
    CONST LIMIT = 10;
    
    private EntityManagerInterface $entityManagerInterface;
    
    private InvoiceRepository $invoiceRepository;
    private InvoiceItemRepository $invoiceItemRepository;
    private PriceListRepository $priceListRepository;
    private CustomerRepository $customerRepository;
    private InvoiceCustomerRepository $invoiceCustomerRepository;
    private ProductRepository $productRepository;
    private CompanyUserRepository $companyUserRepository;
    private PriceRepository $priceRepository;
    
    private ProductService $productService;
    private CustomerService $customerService;
    private InvoiceService $invoiceService;
    private InvoiceItemService $invoiceItemService;
    
    public function __construct(
	    ManagerProvider $managerProvider, 
	    ProductService $productService, 
	    CustomerService $customerService, 
	    InvoiceService $invoiceService, 
	    InvoiceItemService $invoiceItemService) {
	$this->entityManagerInterface = $managerProvider->getDefaultManager();
	$this->invoiceRepository = $this->entityManagerInterface->getRepository(Invoice::class);
	$this->invoiceItemRepository = $this->entityManagerInterface->getRepository(InvoiceItem::class);
	$this->priceListRepository = $this->entityManagerInterface->getRepository(PriceList::class);
	$this->customerRepository = $this->entityManagerInterface->getRepository(Customer::class);
	$this->invoiceCustomerRepository = $this->entityManagerInterface->getRepository(InvoiceCustomer::class);
	$this->productRepository = $this->entityManagerInterface->getRepository(Product::class);
	$this->companyUserRepository = $this->entityManagerInterface->getRepository(CompanyUser::class);
	$this->priceRepository = $this->entityManagerInterface->getRepository(Price::class);
	$this->productService = $productService;
	$this->customerService = $customerService;
	$this->invoiceService = $invoiceService;
	$this->invoiceItemService = $invoiceItemService;
    }
    
    public function createInvoice(\stdClass $invoiceData, string $userInternalID):?string{
	// pricelist
	$priceListInternalID = $invoiceData->priceList;
	$priceList = $this->getPriceListByInternalID($priceListInternalID);
	
	// payment method
	$paymentMethod = $invoiceData->paymentMethod;
	
	$user = $this->getUserByInternalID($userInternalID);
	if(empty($user)){
	    return null;
	}
	
	$customerInternalID = $invoiceData->customerInternalID;
	if(!empty($customerInternalID)){
	    $customer = $this->customerRepository->findOneBy(['internalID' => $customerInternalID]);
	}
	
	// invoice creation
	$invoice = $this->invoiceService->createInvoiceStructure($invoiceData, $user, $customer, $priceList, $paymentMethod);
	$this->entityManagerInterface->persist($invoice);
	// set invoice and customer
	$customer->addInvoice($invoice);
	
	// invoice customer creation
	$invoiceCustomerData = $invoiceData->customer;
	$invoiceCustomer = $this->invoiceService->createInvoiceCustomerStructure($invoiceCustomerData, $invoice);
	// set
	$invoiceCustomer->setInvoice($invoice);
	$invoice->setInvoiceCustomer($invoiceCustomer);
	
	// invoice customer billing address creation
	$invoiceCustomerBillingAddressData = $invoiceData->customer->customerBillingAddress;
	$invoiceCustomerBillingAddress = $this->invoiceService->createInvoiceCustomerBillingAddressStructure($invoiceCustomerBillingAddressData, $invoiceCustomer);
	// set
	$invoiceCustomerBillingAddress->setInvoiceCustomer($invoiceCustomer);
	
	$invoiceCustomer->setInvoiceCustomerBillingAddress($invoiceCustomerBillingAddress);
	
	// invoice customer delivery address creation
	$invoiceCustomerDeliveryAddress = null;
	if($invoiceData->isDifferentDeliveryAddress){
	    $invoiceCustomerDeliveryAddressData = $invoiceData->customer->customerDeliveryAddress;
	    $invoiceCustomerDeliveryAddress = $this->invoiceService->createInvoiceCustomerDeliveryAddressStructure($invoiceCustomerDeliveryAddressData, $invoiceCustomer);
	    $invoiceCustomerDeliveryAddress->setInvoiceCustomer($invoiceCustomer);
	    $invoiceCustomer->setInvoiceCustomerDeliveryAddress($invoiceCustomerDeliveryAddress);
	}
	
	// invoice cnt and sum data
	$invoiceProducts = $invoiceData->multiplier;
	$invoiceProductsChecked = $this->checkProductsExistence($invoiceProducts);
	$invoiceItems = [];
	$invoiceTotal = 0;
	foreach($invoiceProductsChecked as $invoiceProductCheckedData){
	    $invoiceItems[] = $invoiceItem = $this->invoiceItemService->createInvoiceItemStructure($invoiceProductCheckedData,$invoice);
	    
	    if(!empty($invoiceItem)){
		$invoiceTotal += $invoiceItem->getTotalPrice();
		$invoice->addInvoiceItem($invoiceItem);
	    }
	}
	
	if(empty($invoiceItems)){
	    return false;
	}
	
	$invoice->setTotal($invoiceTotal);
	
	try{
	    $this->entityManagerInterface->flush();
	    return $invoice->getInternalID();
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
	
	return null;
    }
    
    public function getInvoice(string $invoiceInternalID): ?Invoice{
	return $this->invoiceRepository->findOneBy(['internalID' => $invoiceInternalID]);
    }
    
    public function getInvoicesCount():int{
	
	return $this->invoiceRepository->count();
    }
    
    public function getPage():int{
	return self::PAGE;
    }
    
    public function getLimit():int{
	return self::LIMIT;
    } 
   
    public function getPaginatedInvoices(int $page, string $searchSlug, array $statusCode):?Paginator{
	
	return $this->invoiceRepository->findPaginated($page,$searchSlug, $statusCode);
    }
    
    public function getPriceLists():array{
	
	return $this->priceListRepository->findAll();
    }
    
    public function getProductAutocomplete(string $slug):?string{
	
	return $this->productService->getProductAutocomplete($slug);
    }
    
    public function getCustomerAutocomplete(string $slug):?string{
	
	return $this->customerService->getCustomerAutocomplete($slug);
    }
    
    public function getCustomerData(string $customerInternalID, bool $asArray = false):array{
	
	/** @var Customer $customer */
	$customer = $this->customerRepository->findOneBy(['internalID' => $customerInternalID]);
	if(empty($customer)){
	    return [];
	}
	
	if(!$asArray){
	    return $customer;
	}
	
	$priceListInternalID = $customer?->getPriceList()?->getInternalID() ?? $this->priceListRepository?->getDefaultPriceList()?->getInternalID();
	
	$customerArr = $customer->toArray();
	$customerArr['billingAddress'] = $customer->getCustomerBillingAddress()->toArray();
	$customerArr['deliveryAddress'] = $customer->getCustomerDeliveryAddress()?->toArray();
	$customerArr['priceListInternalID'] = $priceListInternalID;
	
	return $customerArr;
    }
    
    public function getProductArrayData(string $productInternalID, string $priceListInternalID):array{
	
	/** @var Product $product */
	$product = $this->productRepository->findOneBy(['internalID' => $productInternalID]);
	if(empty($product)){
	    return [];
	}
	$productID = $product->getId();
	
	/** @var PriceList $priceList */
	$priceList = $this->getPriceListByInternalID($priceListInternalID) ?? $this->priceListRepository->getDefaultPriceList();
	if(empty($priceList)){
	    return [];
	}
	$priceListID = $priceList?->getId();
	
	$productPrice = $this->priceRepository->findByProductID($productID, $priceListID);
	
	$productArr = $product->toArray();
	$productArr['priceValue'] = 0;
	if(!empty($productPrice)){
	    $productArr['priceValue'] = $productPrice->getValue();
	}
	
	return $productArr;
    }
    
    public function getPriceListByInternalID(string $priceListInternalID, bool $asArray = false): PriceList|array|null{
	
	$priceList = $this->priceListRepository->findOneBy(['internalID' => $priceListInternalID]);
	if(empty($priceList)){
	    return [];
	}
	
	return $asArray ? $priceList->toArray() : $priceList;
    }
    
    public function getUserByInternalID(string $userInternalID):?CompanyUser{
	$user = $this->companyUserRepository->findOneBy(['internalID' => $userInternalID]);
	if(empty($user)){
	    return null;
	}
	
	return $user;
    }
    
    public function getPrices(string $priceListInternalID, array $productInternalIDs, array $productCatalogueCodes):array{
	
	/** @var PriceList $priceList */
	$priceList = $this->getPriceListByInternalID($priceListInternalID) ?? $this->priceListRepository->getDefaultPriceList();
	$priceListID = null;
	if(!empty($priceList)){
	    $priceListID = $priceList->getId();
	} else {
	    return [];
	}
	
	$products = $this->productRepository->findByInternalIDsAndCatalogueCodes($productInternalIDs, $productCatalogueCodes);
	$productIDs = [];
	/** @var Product $product */
	foreach($products as $product){
	    $productIDs[] = $product->getId();
	}
	
	if(empty($productIDs)){
	    return [];
	}
	
	$prices = $this->priceRepository->findMultipleByProductIDs($productIDs, $priceListID);
	$pricesValuesByProductInternalID = [];
	/** @var Price $price */
	foreach($prices as $price){
	    $pricesValuesByProductInternalID[$price->getProduct()->getInternalID()] = $price->getValue();
	}
	
	return $pricesValuesByProductInternalID;
    }
    
    public function updateInvoice(\stdClass $invoiceData):bool{
	bdump($invoiceData);

	$invoiceInternalID = $invoiceData->invoiceInternalID;
	if(empty($invoiceInternalID)){
	    // LOG
	    return false;
	}
	
	/** @var Invoice $invoice */
	$invoice = $this->invoiceRepository->findOneBy(['internalID' => $invoiceInternalID]);
	if(empty($invoice)){
	    // LOG
	    return false;
	}
	
	$priceListInternalID = $invoiceData->priceList;
	$priceList = $this->getPriceListByInternalID($priceListInternalID);
	
	$invoice->setPriceList($priceList);
	
	$invoiceCustomerInternalID = $invoice->getInvoiceCustomer()->getInternalID();
	if(empty($invoiceCustomerInternalID)){
	    // LOG
	    return false;
	}
	
	$invoiceCustomer = $this->invoiceCustomerRepository->findOneBy(['internalID' => $invoiceCustomerInternalID]);
	if(empty($invoiceCustomer)){
	    // LOG
	    return false;
	}
	
	// invoice update
	$invoice->setDocumentDate($invoiceData->date);
	$invoice->setDueDate($invoiceData->dueDate);
	$invoice->setStatus($invoiceData->state);
	$invoice->setPaymentMethod($invoiceData->paymentMethod);
	
	$invoiceCustomerData = $invoiceData->customer;
	
	$invoiceCustomer->setName($invoiceCustomerData->name);
	$invoiceCustomer->setEmail($invoiceCustomerData->email);
	$invoiceCustomer->setPhone($invoiceCustomerData->phone);
	$invoiceCustomer->setCompanyNumber($invoiceCustomerData->companyNumber);
	$invoiceCustomer->getVatNumber($invoiceCustomerData->vatNumber);

	// invoice customer billing address update
	$invoiceCustomerBillingAddressDataDB = $invoice->getInvoiceCustomer()->getInvoiceCustomerBillingAddress();

	$invoiceCustomerBillingAddressData = $invoiceData->customer->customerBillingAddress;
	$invoiceCustomerBillingAddress = $this->invoiceService->createInvoiceCustomerBillingAddressStructure($invoiceCustomerBillingAddressData, $invoiceCustomer);
	$invoiceCustomerBillingAddressDataDB->setStreet($invoiceCustomerBillingAddress->getStreet());
	$invoiceCustomerBillingAddressDataDB->setCity($invoiceCustomerBillingAddress->getCity());
	$invoiceCustomerBillingAddressDataDB->setZip($invoiceCustomerBillingAddress->getZip());
	$invoiceCustomerBillingAddressDataDB->setCountry($invoiceCustomerBillingAddress->getCountry());
	$invoiceCustomerBillingAddressDataDB->setCountryISO($invoiceCustomerBillingAddress->getCountryISO());
	
	// invoice customer delivery address update
	$invoiceCustomerDeliveryAddress = null;
	$invoiceCustomerDeliveryAddressData = $invoiceData->customer->customerDeliveryAddress;
	$invoiceCustomerDeliveryAddressDataDB = $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress();
	if(!empty($invoiceCustomerDeliveryAddressDataDB) && $invoiceData->isDifferentDeliveryAddress){
	    $invoiceCustomerDeliveryAddress = $this->invoiceService->createInvoiceCustomerDeliveryAddressStructure($invoiceCustomerDeliveryAddressData, $invoiceCustomer);
	    
	    $invoiceCustomerDeliveryAddressDataDB->setStreet($invoiceCustomerDeliveryAddress->getStreet());
	    $invoiceCustomerDeliveryAddressDataDB->setCity($invoiceCustomerDeliveryAddress->getCity());
	    $invoiceCustomerDeliveryAddressDataDB->setZip($invoiceCustomerDeliveryAddress->getZip());
	    $invoiceCustomerDeliveryAddressDataDB->setCountry($invoiceCustomerDeliveryAddress->getCountry());
	    $invoiceCustomerDeliveryAddressDataDB->setCountryISO($invoiceCustomerDeliveryAddress->getCountryISO());
	    
	} else if(empty($invoiceCustomerDeliveryAddressDataDB) && $invoiceData->isDifferentDeliveryAddress){
	    $invoiceCustomerDeliveryAddress = $this->invoiceService->createInvoiceCustomerDeliveryAddressStructure($invoiceCustomerDeliveryAddressData, $invoiceCustomer);
	    $invoiceCustomer->setInvoiceCustomerDeliveryAddress($invoiceCustomerDeliveryAddressDataDB);
	} else if(!empty($invoiceCustomerDeliveryAddressDataDB) && !$invoiceData->isDifferentDeliveryAddress){
	    $this->entityManagerInterface->remove($invoiceCustomerDeliveryAddressDataDB);
	}
	
	$invoiceProducts = $invoiceData->multiplier;
	$invoiceProductsChecked = $this->checkProductsExistence($invoiceProducts);
	$invoiceItemsDB = $invoice->getInvoiceItems();

	$invoiceItems = [];
	$invoiceItemTotalPrice = 0; 
	$invoiceItemTotalPriceWithVAT = 0; 
	foreach($invoiceProductsChecked as $invoiceProductChecked){
	    
	    /** @var InvoiceItem $invoiceItemDB */
	    foreach($invoiceItemsDB as $invoiceItemDB){
		if($invoiceProductChecked['invoiceItemInternalID'] === $invoiceItemDB->getInternalID()){
		    $invoiceItemDB->setProduct($invoiceProductChecked['product'] ?? null);
		    $invoiceItemDB->setCatalogueCode($invoiceProductChecked['catalogueCode']);
		    $invoiceItemDB->setName($invoiceProductChecked['name']);
		    $invoiceItemDB->setPrice((float)$invoiceProductChecked['priceWithoutVAT']);
		    $invoiceItemDB->setQuantity((int)$invoiceProductChecked['quantity']);
		    $invoiceItemDB->setDiscount((float)$invoiceProductChecked['discount']);
		    $invoiceItemDB->setVATRateValue((float)$invoiceProductChecked['vatPercentageValue']);
		    $invoiceItemDB->setTotalPrice((float)$invoiceProductChecked['totalItemPriceWithoutVAT']);
		    $invoiceItemDB->setTotalPriceWithVAT((float)$invoiceProductChecked['totalItemPriceWithVAT']);
		} else {
		    $invoiceItemDB = $invoiceItem = $this->invoiceItemService->createInvoiceItemStructure($invoiceProductChecked,$invoice);
		}
		
		if(empty($invoiceItemDB)){
		    continue;
		}
		
		$invoiceItemTotalPrice += $invoiceItemDB->getTotalPrice();
		$invoiceItemTotalPriceWithVAT += $invoiceItemDB->getTotalPriceWithVAT();
			
		$invoiceItems[] = $invoiceItemDB;
	    }
	}
	$invoice->syncItems($invoiceItems);
	
	$invoice->setTotal($invoiceItemTotalPrice);
	$invoice->setTotalWithVAT($invoiceItemTotalPriceWithVAT);
	
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
    
    private function checkProductsExistence(array|\Nette\Utils\ArrayHash $invoiceProducts):array{
	$checkedProducts = [];
	foreach($invoiceProducts as $invoiceProduct){
	    $invoiceProductInternalID = $invoiceProduct['productInternalID'];
	    $catalogueCode = $invoiceProduct['catalogueCode'];
	    
	    $invoiceProduct['product'] = null;
	    if($invoiceProductInternalID){
		$catalogueCode = $invoiceProduct['catalogueCode'];
		$confirmedProduct = $this->productRepository->findOneBy(['internalID' => $invoiceProductInternalID, 'catalogueCode' => $catalogueCode]);

		$invoiceProduct['productInternalID'] = isset($confirmedProduct) ? $invoiceProduct['productInternalID'] : null;
		$invoiceProduct['product'] = $confirmedProduct;
	    }
	    
	    $checkedProducts[] = (array)$invoiceProduct;
	}
	
	return $checkedProducts;
    }
    
    public function deleteInvoice(Invoice $invoice){
	if(empty($invoice)){
	    return false;
	}
	
	try {
	    $this->entityManagerInterface->remove($invoice);
	    $this->entityManagerInterface->flush();
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    //echo "Flush failed: " . $dcEx->getMessage();
	    return false;
	}
	
	return true;
    }
}
