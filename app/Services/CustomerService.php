<?php
declare(strict_types=1);
namespace App\Services;

use App\Helpers\UUIDGenerator;
use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Html;

use Nette\Security\Passwords;

use App\Database\Customer;
use App\Database\CustomerBillingAddress;
use App\Database\CustomerDeliveryAddress;
use App\Database\PriceList;

use App\Database\CustomerRepository;
use App\Database\PriceListRepository;

class CustomerService {
    
    CONST CUSTOMER_DEF_ROLE = 'customer';
    CONST CUSTOMER_AUTOCOMPLETE_LIMIT = 5;
    
    private Passwords $passwords;
    private EntityManagerInterface $entityManagerInterface;
    private CustomerRepository $customerRepository;
    private PriceListRepository $priceListRepository;
    
    private array $countries = [];
    
    public function __construct(Passwords $passwords, ManagerProvider $entityManagerProvider) {
	$this->passwords = $passwords;
	$this->entityManagerInterface = $entityManagerProvider->getDefaultManager();
	$this->customerRepository = $this->entityManagerInterface->getRepository(Customer::class);
	$this->priceListRepository = $this->entityManagerInterface->getRepository(PriceList::class);
	
	
	$path = '../App/Resources/countries.json';
	$countriesJSON = file_get_contents($path);
	$this->countries = json_decode($countriesJSON, true);
    }
    
    public function createCustomerStructure($customerDataRaw, ?PriceList $priceList = null):?Customer{
	$internalID = UUIDGenerator::generateInternalID();
	
	
	$customerData = null;
	if(isset($customerDataRaw->formCustomerAutocomplete->masterInternalID)){
	    /** @var Customer $customerData */
	    $masterInternalID = $customerDataRaw->formCustomerAutocomplete->masterInternalID;
	    $customerData = $this->customerRepository->findOneBy(['internalID' => $masterInternalID]);;
	}

	if(empty($priceList)){
	    $priceList = null;//$this->priceListRepository?->getMainPriceList() ?? null;
	}
	
	return new Customer(
		$internalID, 
		$customerDataRaw->identificator,
		$customerDataRaw->name, 
		$customerDataRaw->companyNumber, 
		$customerDataRaw->vatNumber, 
		$customerDataRaw?->note, 
		(string)$customerDataRaw->phone, 
		$customerDataRaw->email, 
		$customerDataRaw?->dueDays ?? 0, 
		$priceList,
		null,
		null,
		$customerDataRaw->isEnabled
		);
    }
    
    public function createCustomerBillingAddressSctructure($customerBillingAddressDataRaw,$customer):?CustomerBillingAddress{
	$internalID = UUIDGenerator::generateInternalID();
	$countryISO = $customerBillingAddressDataRaw->country;
	$countryName = $this->findCountryByISO($countryISO);
	
	return new CustomerBillingAddress(
		$internalID, 
		$customer, 
		$customerBillingAddressDataRaw->street, 
		$customerBillingAddressDataRaw->city, 
		$customerBillingAddressDataRaw->zip,
		$countryName,
		$countryISO
		);
    }
    
    public function createCustomerDeliveryAddressSctructure($customerDeliveryAddressDataRaw,$customer):?CustomerDeliveryAddress{
	$internalID = UUIDGenerator::generateInternalID();
	$countryISO = $customerDeliveryAddressDataRaw->country;
	$countryName = $this->findCountryByISO($countryISO);
	
	return new CustomerDeliveryAddress(
		$internalID, 
		$customer, 
		$customerDeliveryAddressDataRaw->street, 
		$customerDeliveryAddressDataRaw->city, 
		$customerDeliveryAddressDataRaw->zip,
		$countryName,
		$countryISO
		);
    }
    
     public function getCustomerAutocomplete(string $slug):?string{
	
	$customers = $this->customerRepository->findMultipleBySlug($slug,self::CUSTOMER_AUTOCOMPLETE_LIMIT);
	if(empty($customers)){
	    return null;
	}
	
	$customerHtml = "";
	/** @var Customer $customer */
	foreach ($customers as $customer) {
	    $autocompleteText = $customer->getName().' | '.$customer->getIdentificator();
	    
	    $el = Html::el('p')
		->addText($autocompleteText)
		->addAttributes([
		    'class' => 'customer-autocomplete-suggestion',
		    'data-customer-internal-id' => $customer->getInternalID()
		]);

	    $customerHtml .= $el->toHtml();
	}

	return $customerHtml;
    }
    
    public function findCountryByISO(string $countryISO){
	$filteredISO = array_filter(
		$this->countries, 
		fn($key) => $key === $countryISO,
		ARRAY_FILTER_USE_KEY
	);
	
	$countryNameArr = array_values($filteredISO);
	
	return isset($countryNameArr[0]) ? $countryNameArr[0] : null;
    }
    
    public function generateCustomerIdentificator(): string{
	$year = date('Y');
	$prefix = 'CUST';

	$lastIdentificator = $this->customerRepository->findLastCustomerIdentificator();

	if (!empty($lastIdentificator)) {
	    preg_match('/(\d{4})$/', $lastIdentificator, $matches);
	    $lastNumeric = isset($matches[1]) ? (int)$matches[1] : 0;
	} else {
	    $lastNumeric = 0;
	}

	$nextNumeric = str_pad((string)($lastNumeric + 1), 6, '0', STR_PAD_LEFT);

	return "{$prefix}{$year}{$nextNumeric}";
    }
}
