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
	$this->productRepository = $this->entityManagerInterface->getRepository(Product::class);
	$this->companyUserRepository = $this->entityManagerInterface->getRepository(CompanyUser::class);
	$this->priceRepository = $this->entityManagerInterface->getRepository(Price::class);
	$this->productService = $productService;
	$this->customerService = $customerService;
	$this->invoiceService = $invoiceService;
	$this->invoiceItemService = $invoiceItemService;
    }
    
    public function createInvoice(\stdClass $invoiceData, string $userInternalID):?string{
	bdump($invoiceData);
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
	} else {
	    
	    // create a customer after a check of checkbox
	    
	    // create customer
	    /*$priceListInternalID = $invoiceData->priceList;
	    $priceList = null;
	    if(!empty($priceListInternalID)){
		$priceList = $this->priceListRepository->findOneBy(['internalID' => $priceListInternalID]);
	    }
	    
	    $customerData = $invoiceData->customer;
	    $customer = $this->customerService->createCustomerStructure($customerData, $priceList);*/
	    
	    /*$this->entityManagerInterface->persist($customer);
	    $this->entityManagerInterface->flush();*/
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
	bdump($invoiceCustomerBillingAddress);
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

	$this->entityManagerInterface->flush();
	
	return $invoice->getInternalID();
    }
    
    public function getInvoice(string $invoiceInternalID): ?Invoice{
	return $this->invoiceRepository->findOneBy(['internalID' => $invoiceInternalID]);
    }
    
    public function getPage():int{
	return self::PAGE;
    }
    
    public function getLimit():int{
	return self::LIMIT;
    } 
   
    public function getPaginatedInvoices(int $page, string $searchSlug):?Paginator{
	
	return $this->invoiceRepository->findPaginated($page,$searchSlug);
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
    
    public function updateInvoice(\stdClass $invoiceData, string $userInternalID):bool{
	
	$invoiceInternalID = $invoiceData->invoiceInternalID;
	if(empty($invoiceInternalID)){
	    return false;
	}
	
	/** @var Invoice $invoice */
	$invoice = $this->invoiceRepository->findOneBy(['internalID' => $invoiceInternalID]);
	if(empty($invoice)){
	    return false;
	}
	
	$priceListInternalID = $invoiceData->priceList;
	$priceList = $this->getPriceListByInternalID($priceListInternalID);
	
	$invoice->setPriceList($priceList);
	
	$customerInternalIDForm = $invoiceData->customerInternalID;
	$invoiceCustomerInternalID = $invoice->getCustomer()->getInternalID();
	if($customerInternalIDForm !== $invoiceCustomerInternalID){
	    $customer = $this->getCustomerData($customerInternalIDForm);
	    if(empty($customer)){
		// create customer
		$customerData = $invoiceData->customer;
		$customer = $this->customerService->createCustomerStructure($customerData, $priceList);
	    }
	    
	    $invoice->setCustomer($customer);
	}
	
	// invoice update
	$invoice->setDueDate($invoiceData->dueDate);
	
	$invoiceCustomerData = $invoiceData->customer;
	$invoiceCustomer = $this->invoiceService->createInvoiceCustomerStructure($invoiceCustomerData, $invoice);
	
	$invoiceCustomer->setCompanyName($invoiceCustomerData->companyName);
	$invoiceCustomer->setCompanyNumber($invoiceCustomerData->vatNumber);
	$invoiceCustomer->getVatNumber($invoiceCustomerData->vatNumber);
	$invoiceCustomer->setName($invoiceCustomerData->name);
	$invoiceCustomer->setEmail($invoiceCustomerData->email);
	$invoiceCustomer->setPhone($invoiceCustomerData->phone);
	
	// invoice customer billing address update
	$invoiceCustomerBillingAddressDataDB = $invoice->getInvoiceCustomer()->getInvoiceCustomerBillingAddress();

	$invoiceCustomerBillingAddressData = $invoiceData->customer->customerBillingAddress;
	$invoiceCustomerBillingAddress = $this->invoiceService->createInvoiceCustomerBillingAddressStructure($invoiceCustomerBillingAddressData, $invoiceCustomer);
	$invoiceCustomerBillingAddressDataDB->setStreet($invoiceCustomerBillingAddress->getStreet());
	$invoiceCustomerBillingAddressDataDB->setCity($invoiceCustomerBillingAddress->getCity());
	$invoiceCustomerBillingAddressDataDB->setZip($invoiceCustomerBillingAddress->getZip());
	$invoiceCustomerBillingAddressDataDB->setInvoiceCustomer($invoiceCustomer);
	$invoiceCustomer->setInvoiceCustomerBillingAddress($invoiceCustomerBillingAddressDataDB);
	
	// invoice customer delivery address update
	$invoiceCustomerDeliveryAddress = null;
	$invoiceCustomerDeliveryAddressData = $invoiceData->customer->customerDeliveryAddress;
	$invoiceCustomerDeliveryAddressDataDB = $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress();
	if(!empty($invoiceCustomerDeliveryAddressDataDB) && !empty($invoiceData->customer->customerDeliveryAddress->city)){
	    $invoiceCustomerDeliveryAddress = $this->invoiceService->createInvoiceCustomerDeliveryAddressStructure($invoiceCustomerDeliveryAddressData, $invoiceCustomer);
	    
	    $invoiceCustomerDeliveryAddressDataDB->setStreet($invoiceCustomerDeliveryAddress->getStreet());
	    $invoiceCustomerDeliveryAddressDataDB->setCity($invoiceCustomerDeliveryAddress->getCity());
	    $invoiceCustomerDeliveryAddressDataDB->setZip($invoiceCustomerDeliveryAddress->getZip());
	    $invoiceCustomerDeliveryAddressDataDB->setInvoiceCustomer($invoiceCustomer);
	    
	    $invoiceCustomer->setInvoiceCustomerDeliveryAddress($invoiceCustomerDeliveryAddressDataDB);
	} else if(empty($invoiceCustomerDeliveryAddressDataDB) && !empty($invoiceData->customer->customerDeliveryAddress->city)){
	    $invoiceCustomerDeliveryAddress = $this->invoiceService->createInvoiceCustomerDeliveryAddressStructure($invoiceCustomerDeliveryAddressData, $invoiceCustomer);
	    $invoiceCustomer->setInvoiceCustomerDeliveryAddress($invoiceCustomerDeliveryAddressDataDB);
	} else if(!empty($invoiceCustomerDeliveryAddressDataDB) && empty($invoiceData->customer->customerDeliveryAddress->city)){
	    $this->entityManagerInterface->remove($invoiceCustomerDeliveryAddressDataDB);
	}
	
	//bdump();
	
	// invoice customer update
	
	$invoice->setInvoiceCustomer($invoiceCustomer);
	
	$invoiceProducts = $invoiceData->multiplier;
	$invoiceProductsChecked = $this->checkProductsExistence($invoiceProducts);
	$invoiceItemsDB = $invoice->getInvoiceItems();
	bdump($invoiceProductsChecked);
	$invoiceItems = [];
	foreach($invoiceProductsChecked as $invoiceProductChecked){
	    
	    /** @var InvoiceItem $invoiceItemDB */
	    foreach($invoiceItemsDB as $invoiceItemDB){
		if($invoiceItemDB?->getProduct() !== null && $invoiceProductChecked['productID'] === $invoiceItemDB->getProduct()->getInternalID()){
		    $invoiceItemDB->setPrice((float)$invoiceProductChecked['value']);
		} else {
		    $invoiceItemDB = $invoiceItem = $this->invoiceItemService->createInvoiceItemStructure($invoiceProductChecked,$invoice);
		}
		
		$invoiceItems[] = $invoiceItemDB;
	    }
	}
	$invoice->syncItems($invoiceItems);
	
	try{
	    $this->entityManagerInterface->flush();
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    // došlo k chybě při flush
	    //echo "Flush failed: " . $dcEx->getMessage();
	    return false;
	}
    }
    
    private function checkProductsExistence(array|\Nette\Utils\ArrayHash $invoiceProducts):array{
	$checkedProducts = [];
	foreach($invoiceProducts as $invoiceProduct){
	    $invoiceProductInternalID = $invoiceProduct['productID'];
	    $catalogueCode = $invoiceProduct['catalogueCode'];
	    
	    $invoiceProduct['product'] = null;
	    if($invoiceProductInternalID){
		$catalogueCode = $invoiceProduct['catalogueCode'];
		$confirmedProduct = $this->productRepository->findOneBy(['internalID' => $invoiceProductInternalID, 'catalogueCode' => $catalogueCode]);

		$invoiceProduct['productID'] = isset($confirmedProduct) ? $invoiceProduct['productID'] : null;
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
