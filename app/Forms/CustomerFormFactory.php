<?php
declare(strict_types=1);

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Utils\Html;

use App\Database\Customer;
use App\Database\CustomerBillingAddress;
use App\Database\CustomerDeliveryAddress;
use App\Database\InvoiceCustomer;
use App\Database\InvoiceCustomerBillingAddress;
use App\Database\InvoiceCustomerDeliveryAddress;

class CustomerFormFactory {
    public static function createCustomerForm(Form $form,string $presenter,string $customerIdentificator = null, Customer|InvoiceCustomer|null $customer = null, array $priceLists = [],bool $lockCompanyName = false): void {
        
	$customerForm = $form->addContainer('customer');
	
	$customerID = $customerForm->addHidden('customerInternalID')
		->setHtmlId('customer-internal-id');
	
	$isEnabled = $customerForm->addCheckbox('isEnabled','Is enabled')
		->setHtmlId('customer-is-enabled')
		->setDefaultValue(true);
	
	$identificator = $customerForm->addText('identificator','Identificator')
		->setMaxLength(255)
		->addRule($form::MinLength, 'Identificator has to be at least %d chars long!', 3)
		->setHtmlId('customer-identificator')
		->setHtmlAttribute('placeholder','Enter customer unique identificator')
		->setRequired();
	
	$name = $customerForm->addText('name','Name / Company name')
		->setHtmlId('customer-name')
		->setMaxLength(255)
		->setHtmlAttribute('placeholder','e.g. John Doe')
		->setRequired();
        
	if($presenter === 'Customer'){
	    $defaultPriceListInternalID = null;
	    $priceListsForSelect = [];
	    foreach($priceLists as $priceList){
		$priceListsForSelect[$priceList->getInternalID()] = $priceList->getName();

		if($priceList->getIsDefault()){
		    $defaultPriceListInternalID = $priceList->getInternalID();
		}
	    }
	    
	    $priceListSelect = $customerForm->addSelect('priceList','Price list', $priceListsForSelect);
	}
	
	$dueDays = $customerForm->addInteger('dueDays','Invoice due days')
		->setHtmlId('customer-due-days')
		->setHtmlAttribute('placeholder','0')
		->setDefaultValue(0)
		->setHtmlAttribute('min',0);
	
        $companyNumber = $customerForm->addText('companyNumber','Company number')
		->setMaxLength(20)
		->setHtmlId('customer-company-number')
		->setHtmlAttribute('placeholder','Enter company number');
	$vatNumber = $customerForm->addText('vatNumber','Vat number')
		->setMaxLength(9)
		->setHtmlId('customer-vat-number')
		->setHtmlAttribute('placeholder','Enter company VAT number');
	
	$diffDeliveryAddress = $form->addCheckbox('isDifferentDeliveryAddress','Different delivery address');
	
	$path = '../App/Resources/countries.json';
	$countriesJSON = file_get_contents($path);
	$countries = json_decode($countriesJSON, true);
	
	switch($presenter){
	    case 'Customer':
		self::createBillingAddress($customerForm, $customer?->getCustomerBillingAddress(),$countries);
		self::createDeliveryAddress($customerForm,$customer?->getCustomerDeliveryAddress(),$form,$countries);
		$diffDeliveryAddress->setDefaultValue($customer?->getCustomerDeliveryAddress() !== null);
		break;
	}
	
	$diffDeliveryAddress->addCondition($form::Equal, true)
		->toggle("#deliveryAddress");
	
        $note = $customerForm->addTextArea('note','Customer note')
		->setHtmlId('customer-note')
		->setHtmlAttribute('placeholder','Optional')
		->setHtmlAttribute('rows',5);
      
        $email = $customerForm->addEmail('email','Email')
		->setHtmlId('customer-email')
		->setHtmlAttribute('placeholder','e.g. john.doe@gmail.com')
		->setRequired();
        $phone = $customerForm->addText('phone','Phone')
		->setHtmlType('tel')
		->setHtmlId('customer-phone')
		->setHtmlAttribute('placeholder','e.g. 020 7561 1106')
		->setRequired();
	
	if(!empty($customer)){
	    $customerID->setDefaultValue($customer->getInternalID());
	    $name->setDefaultValue($customer->getName());
	    $dueDays->setDefaultValue($customer->getDueDays());
	    $companyNumber->setDefaultValue($customer->getCompanyNumber());
	    $vatNumber->setDefaultValue($customer->getVatNumber());
	    $email->setDefaultValue($customer->getEmail());
	    $phone->setDefaultValue($customer->getPhone());
	 
	    if($presenter === 'Customer'){
		$note->setDefaultValue($customer->getNote());
		$isEnabled->setDefaultValue($customer->getIsEnabled());
		$priceListSelect->setDefaultValue($customer?->getPriceList()?->getInternalID());
	    }
	    $identificator->setHtmlAttribute('readonly',true);
	}
	
	$identificator->setDefaultValue($customerIdentificator);
	
	if($lockCompanyName){
	    $companyName->setHtmlAttribute('readonly');
	}
    }
    
    public static function createBillingAddress(Nette\Forms\Container $form, CustomerBillingAddress|InvoiceCustomerBillingAddress|null $billingAddress, array $countries = []): void{
	
	$formCustomerBillingAddress = $form->addContainer('customerBillingAddress'); 
	$billingAddressStreet = $formCustomerBillingAddress->addText('street','Street 1')
		->setHtmlId('billing-address-street')
		->setHtmlAttribute('placeholder','e.g. Baker Street')
		->setRequired();
        $billingAddressCity = $formCustomerBillingAddress->addText('city','City')
		->setHtmlId('billing-address-city')
		->setHtmlAttribute('placeholder','e.g. London')
		->setRequired();
        $billingAddressZip = $formCustomerBillingAddress->addText('zip','Post code')
		->setMaxLength(7)
		->setHtmlId('billing-address-zip')
		->setHtmlAttribute('placeholder','e.g. NW1 6XE')
		->setRequired();
	$billingAddressCountry = $formCustomerBillingAddress->addSelect('country','Country',$countries)
		->setDefaultValue('CZ')
		->setHtmlId('billing-address-country')
		->setRequired();
	
	if(!empty($billingAddress)){
	    $billingAddressStreet->setDefaultValue($billingAddress->getStreet());
	    $billingAddressCity->setDefaultValue($billingAddress->getCity());
	    $billingAddressZip->setDefaultValue($billingAddress->getZip());
	    $billingAddressCountry->setDefaultValue($billingAddress->getCountryISO());
	}
    }
    
    public static function createDeliveryAddress(Nette\Forms\Container $form, CustomerDeliveryAddress|InvoiceCustomerDeliveryAddress|null $deliveryAddress, Nette\Forms\Container|Form $condition = null, array $countries = []): void{
	
	$formDeliveryAddress = $form->addContainer('customerDeliveryAddress'); 
	$deliveryAddressStreet = $formDeliveryAddress->addText('street','Street 1')
		->setHtmlId('delivery-address-street')
		->setHtmlAttribute('placeholder','e.g. Baker Street');
        $deliveryAddressCity = $formDeliveryAddress->addText('city','City')
		->setHtmlId('delivery-address-city')
		->setHtmlAttribute('placeholder','e.g. London');
        $deliveryAddressZip = $formDeliveryAddress->addText('zip','Post code')
		->setMaxLength(7)
		->setHtmlId('delivery-address-zip')
		->setHtmlAttribute('placeholder','e.g. NW1 6XE');
	$deliveryAddressCountry = $formDeliveryAddress->addSelect('country','Country',$countries)
		->setDefaultValue('CZ')
		->setHtmlId('delivery-address-country');
	
	if(!empty($deliveryAddress)){
	    $deliveryAddressStreet->setDefaultValue($deliveryAddress->getStreet());
	    $deliveryAddressCity->setDefaultValue($deliveryAddress->getCity());
	    $deliveryAddressZip->setDefaultValue($deliveryAddress->getZip());
	    $deliveryAddressCountry->setDefaultValue($deliveryAddress->getCountryISO());
	}
	
	if($condition instanceof Form){
	    $deliveryAddressStreet->addConditionOn($condition['isDifferentDeliveryAddress'],Form::Filled)
		    ->setRequired('Fill the delivery address input!');
	    $deliveryAddressCity->addConditionOn($condition['isDifferentDeliveryAddress'],Form::Filled)
			->setRequired('Fill the city input!');
	    $deliveryAddressZip->addConditionOn($condition['isDifferentDeliveryAddress'],Form::Filled)
			->setRequired('Fill the zip input!');
	    $deliveryAddressCountry->addConditionOn($condition['isDifferentDeliveryAddress'],Form::Filled)
			->setRequired('Fill the zip input!');
	}
    }
    
    public static function createRoleSelection(Nette\Forms\Container $formContainer, array $roles = []): void{
	$formRole = $formContainer->addContainer('formCustomerRole');
	$formRole->addSelect('roles',$roles);
    }
    
    public static function createPassword(Nette\Forms\Container $formContainer){
	$formPassword = $formContainer->addContainer('formCustomerPassword');
	$password = $formPassword->addPassword('password','Password')->setRequired();
	$passwordCheck = $formPassword->addPassword('repeatPassword','Password again')
		->setOmitted()
		->setHtmlAttribute('autocomplete','off');
	$password->addRule(Form::MinLength, 'Password has to be at least %d characters long!', 8);
	
	$passwordCheck->addRule(Form::Equal,'Passwords do not match!',$password);
    }
}
