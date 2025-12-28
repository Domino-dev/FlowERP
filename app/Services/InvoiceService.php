<?php
declare(strict_types=1);
namespace App\Services;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

use App\Database\Customer;
use App\Database\PaymentMethod;
use App\Database\PriceList;
use App\Database\Product;
use App\Database\CompanyUser;
use App\Database\Invoice;
use App\Database\InvoiceCustomer;
use App\Database\InvoiceCustomerBillingAddress;
use App\Database\InvoiceCustomerDeliveryAddress;

use App\Database\InvoiceRepository;
use App\Database\InvoiceCustomerRepository;
use App\Database\InvoiceCustomerBillingAddressRepository;
use App\Database\InvoiceCustomerDeliveryAddressRepository;

class InvoiceService {
    
    private EntityManagerInterface $emi;
    
    private InvoiceRepository $invoiceRepository;
    private InvoiceCustomerRepository $invoiceCustomerRepository;
    private InvoiceCustomerBillingAddressRepository $invoiceCustomerBillingAddressRepository;
    private InvoiceCustomerDeliveryAddressRepository $invoiceCustomerDeliveryAddressRepository;
    
    private array $countries = [];
    
    public function __construct(ManagerProvider $managerProvider) {
	$this->emi = $managerProvider->getDefaultManager();
	$this->invoiceRepository = $this->emi->getRepository(Invoice::class);
	
	$path = '../App/Resources/countries.json';
	$countriesJSON = file_get_contents($path);
	$this->countries = json_decode($countriesJSON, true);
    }
    
    public function createInvoiceStructure(\stdClass $invoiceData,CompanyUser $user, ?Customer $customer, PriceList $priceList,string $paymentMethod): Invoice{
	
	$number = $this->createInvoiceNumber();
	
	$invoiceSumAndCnt = $this->getInvoiceItemsSumAndCnt($invoiceData->multiplier);
	
	return new Invoice(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		$user, 
		$customer, 
		$priceList,
		$number, 
		$paymentMethod,
		$invoiceSumAndCnt['sumWithoutVAT'], 
		$invoiceSumAndCnt['sumWithVAT'], 
		$invoiceSumAndCnt['cnt'],  
		0,
		$invoiceData->state, 
		$invoiceData->dueDate, 
		$invoiceData->date);
    }
    
    public function createInvoiceCustomerStructure(\stdClass $invoiceCustomerData, Invoice $invoice): InvoiceCustomer{
	
	$dueDays = 0;
	if(!empty($invoice->getDueDate())){
	    $dateDiff = date_diff($invoice->getDocumentDate(),$invoice->getDueDate());
	    $dueDays = $dateDiff->days;
	}
	
	return new InvoiceCustomer(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		$invoiceCustomerData->name, 
		$invoiceCustomerData->companyNumber, 
		$invoiceCustomerData->vatNumber, 
		$invoice, 
		$invoiceCustomerData->phone, 
		$invoiceCustomerData->email, 
		$dueDays
		);
    }
    
    public function createInvoiceCustomerBillingAddressStructure(\stdClass $invoiceCustomerBillingData, InvoiceCustomer $invoiceCustomer): InvoiceCustomerBillingAddress{
	
	$countryISO = $invoiceCustomerBillingData->country;
	bdump($countryISO);
	$countryName = $this->findCountryByISO($countryISO);
	bdump($countryName);
	return new InvoiceCustomerBillingAddress(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		$invoiceCustomer, 
		$invoiceCustomerBillingData->street, 
		$invoiceCustomerBillingData->city, 
		$invoiceCustomerBillingData->zip,
		$countryName,
		$countryISO
		);
    }
    
    public function createInvoiceCustomerDeliveryAddressStructure(\stdClass $invoiceCustomerDeliveryData, InvoiceCustomer $invoiceCustomer): InvoiceCustomerDeliveryAddress{
	
	$countryISO = $invoiceCustomerDeliveryData->country;
	$countryName = $this->findCountryByISO($countryISO);
	
	return new InvoiceCustomerDeliveryAddress(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		$invoiceCustomer, 
		$invoiceCustomerDeliveryData->street, 
		$invoiceCustomerDeliveryData->city, 
		$invoiceCustomerDeliveryData->zip,
		$countryName,
		$countryISO
		);
    }
    
    
    private function createInvoiceNumber(): string{
	$year = date('Y');
	$month = date('m');
	$prefix = 'INV';

	// Get the last invoice (ordered descending by number)
	$lastInvoice = $this->invoiceRepository->findBy([], ['number' => 'DESC'], 1);

	if (!empty($lastInvoice)) {
	    $lastNumber = $lastInvoice->getNumber(); // Assuming an object with getNumber()
	    // Extract the numeric sequence at the end
	    preg_match('/(\d+)$/', $lastNumber, $matches);
	    $lastNumeric = isset($matches[1]) ? (int)$matches[1] : 0;
	} else {
	    $lastNumeric = 0;
	}

	// Increment and pad the new number
	$nextNumeric = str_pad((string)($lastNumeric + 1), 6, '0', STR_PAD_LEFT);

	// Construct new invoice number
	return "{$prefix}-{$year}-{$month}-{$nextNumeric}";
    }
    
    public function getInvoiceItemsSumAndCnt(array|\Nette\Utils\ArrayHash $invoiceItems): array{
	$invoiceItemsSumWithoutVAT = 0;
	$invoiceItemsSumWithVAT = 0;
	foreach($invoiceItems as $invoiceItem){
	    $price = (float)$invoiceItem['priceWithoutVAT'];
	    $quantity = (int)$invoiceItem['quantity'];
	    $vatRate = (float)$invoiceItem['vatPercentageValue'];
	    $discount = (float)$invoiceItem['discount'];
	    $invoiceItemsSumWithoutVAT += $totalPrice = round($price*$quantity*(1-$discount/100),2);
	    $invoiceItemsSumWithVAT += round($totalPrice*(1-$vatRate/100),2);
	}
	
	return [
	    'sumWithoutVAT' => $invoiceItemsSumWithoutVAT,
	    'sumWithVAT' => $invoiceItemsSumWithVAT,
	    'cnt' =>  count($invoiceItems)
	];
    }
    
    private function findCountryByISO(string $countryISO){
	$filteredISO = array_filter(
		$this->countries, 
		fn($key) => $key === $countryISO,
		ARRAY_FILTER_USE_KEY
	);
	
	$countryNameArr = array_values($filteredISO);
	
	return isset($countryNameArr[0]) ? $countryNameArr[0] : null;
    }
}
