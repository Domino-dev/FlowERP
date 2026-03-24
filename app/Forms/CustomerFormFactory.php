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
    public static function createCustomerForm(Form $form,bool $readOnly,string $presenter,string $customerIdentificator = null, Customer|InvoiceCustomer|null $customer = null, array $priceLists = [],bool $lockCompanyName = false): void {
        
	$customerForm = $form->addContainer('customer');
	
	$customerID = $customerForm->addHidden('customerInternalID')
		->setHtmlId('customer-internal-id');
	
	$identificator = $customerForm->addText('identificator','Identificator')
		->setMaxLength(255)
		->addRule($form::MinLength, 'Identificator has to be at least %d chars long!', 3)
		->setHtmlId('customer-identificator')
		->setHtmlAttribute('placeholder','Enter customer unique identificator')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
	
	$name = $customerForm->addText('name','Name / Company name')
		->setHtmlId('customer-name')
		->setMaxLength(255)
		->setHtmlAttribute('placeholder','e.g. John Doe')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
        
	if($presenter === 'Customer'){
	    
	    $isEnabled = $customerForm->addCheckbox('isEnabled','Is enabled')
		->setHtmlId('customer-is-enabled')
		->setDefaultValue(true);
	    
	    $defaultPriceListInternalID = null;
	    $priceListsForSelect = [];
	    foreach($priceLists as $priceList){
		$priceListsForSelect[$priceList->getInternalID()] = $priceList->getName();

		if($priceList->getIsDefault()){
		    $defaultPriceListInternalID = $priceList->getInternalID();
		}
	    }
	    
	    $priceListSelect = $customerForm->addSelect('priceList','Price list', $priceListsForSelect);
	    
	    $note = $customerForm->addTextArea('note','Customer note')
		->setHtmlId('customer-note')
		->setHtmlAttribute('placeholder','Optional')
		->setHtmlAttribute('readonly',$readOnly)
		->setHtmlAttribute('rows',5);
	}
	
	$dueDays = $customerForm->addInteger('dueDays','Invoice due days')
		->setHtmlId('customer-due-days')
		->setHtmlAttribute('placeholder','0')
		->setHtmlAttribute('readonly',$readOnly)
		->setDefaultValue(0)
		->setHtmlAttribute('min',0);
	
        $companyNumber = $customerForm->addText('companyNumber','Company number')
		->setMaxLength(20)
		->setHtmlId('customer-company-number')
		->setHtmlAttribute('placeholder','Enter company number')
		->setHtmlAttribute('readonly',$readOnly);
	$vatNumber = $customerForm->addText('vatNumber','Vat number')
		->setMaxLength(9)
		->setHtmlId('customer-vat-number')
		->setHtmlAttribute('placeholder','Enter company VAT number')
		->setHtmlAttribute('readonly',$readOnly);
	
	if(!$readOnly){
	    $diffDeliveryAddress = $customerForm->addCheckbox('isDifferentDeliveryAddress','Different delivery address');
	}
	
	if(!empty($customer)){
	    
	} else {
	    $countries = \App\Helpers\Country::getCountries();
	}
	
	switch($presenter){
	    case 'Customer':
		self::createBillingAddress($customerForm, $readOnly,$customer?->getCustomerBillingAddress());
		self::createDeliveryAddress($customerForm, $readOnly,$customer?->getCustomerDeliveryAddress(),$form);
		
		if(!$readOnly){
		    $diffDeliveryAddress->setDefaultValue($customer?->getCustomerDeliveryAddress() !== null);
		}
		break;
	    case 'Invoice':
		self::createBillingAddress($customerForm,$readOnly, $customer?->getInvoiceCustomerBillingAddress());
		self::createDeliveryAddress($customerForm,$readOnly,$customer?->getInvoiceCustomerDeliveryAddress(),$form);
		
		if(!$readOnly){
		    $diffDeliveryAddress->setDefaultValue($customer?->getInvoiceCustomerDeliveryAddress() !== null);
		}
		break;
	}
	
	if(!$readOnly){
	    $diffDeliveryAddress->addCondition($form::Equal, true)
		    ->toggle("deliveryAddress");
	}
      
        $email = $customerForm->addEmail('email','Email')
		->setHtmlId('customer-email')
		->setHtmlAttribute('placeholder','e.g. john.doe@gmail.com')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
        $phone = $customerForm->addText('phone','Phone')
		->setHtmlType('tel')
		->setHtmlId('customer-phone')
		->setHtmlAttribute('placeholder','e.g. 020 7561 1106')
		->setHtmlAttribute('readonly',$readOnly)
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
    }
    
    public static function createBillingAddress(Nette\Forms\Container $form, bool $readOnly,CustomerBillingAddress|InvoiceCustomerBillingAddress|null $billingAddress): void{
	
	if(!empty($billingAddress) && $readOnly){
	    $countries = \App\Helpers\Country::getCountries($billingAddress->getCountryISO());
	} else {
	    $countries = \App\Helpers\Country::getCountries();
	}
	
	$formCustomerBillingAddress = $form->addContainer('customerBillingAddress'); 
	$billingAddressStreet = $formCustomerBillingAddress->addText('street','Street 1')
		->setHtmlId('billing-address-street')
		->setHtmlAttribute('placeholder','e.g. Baker Street')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
        $billingAddressCity = $formCustomerBillingAddress->addText('city','City')
		->setHtmlId('billing-address-city')
		->setHtmlAttribute('placeholder','e.g. London')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
        $billingAddressZip = $formCustomerBillingAddress->addText('zip','Post code')
		->setMaxLength(7)
		->setHtmlId('billing-address-zip')
		->setHtmlAttribute('placeholder','e.g. NW1 6XE')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
	$billingAddressCountry = $formCustomerBillingAddress->addSelect('country','Country',$countries);
	
	if(!empty($billingAddress)){
	    $billingAddressStreet->setDefaultValue($billingAddress->getStreet());
	    $billingAddressCity->setDefaultValue($billingAddress->getCity());
	    $billingAddressZip->setDefaultValue($billingAddress->getZip());
	    $billingAddressCountry->setDefaultValue($billingAddress->getCountryISO());
	} else {
	   $billingAddressCountry->setDefaultValue('CZ');
	}
	
	$billingAddressCountry->setHtmlId('billing-address-country')
		->setHtmlAttribute('readonly',$readOnly)
		->setRequired();
    }
    
    public static function createDeliveryAddress(Nette\Forms\Container $form, bool $readOnly,CustomerDeliveryAddress|InvoiceCustomerDeliveryAddress|null $deliveryAddress, Nette\Forms\Container|Form $condition = null): void{
	
	if(!empty($deliveryAddress) && $readOnly){
	    $countries = \App\Helpers\Country::getCountries($deliveryAddress->getCountryISO());
	} else {
	    $countries = \App\Helpers\Country::getCountries();
	}
	
	
	$formDeliveryAddress = $form->addContainer('customerDeliveryAddress'); 
	$deliveryAddressStreet = $formDeliveryAddress->addText('street','Street 1')
		->setHtmlId('delivery-address-street')
		->setHtmlAttribute('placeholder','e.g. Baker Street')
		->setHtmlAttribute('readonly',$readOnly);
		
	if(!$readOnly){
	    $deliveryAddressStreet->addConditionOn($form['isDifferentDeliveryAddress'],Form::Equal, true)->setRequired('Fill the delivery address input!');
	}
	
        $deliveryAddressCity = $formDeliveryAddress->addText('city','City')
		->setHtmlId('delivery-address-city')
		->setHtmlAttribute('placeholder','e.g. London')
		->setHtmlAttribute('readonly',$readOnly);
	
	if(!$readOnly){
	    $deliveryAddressCity->addConditionOn($form['isDifferentDeliveryAddress'],Form::Equal, true)->setRequired('Fill the city input!');
	}
	
        $deliveryAddressZip = $formDeliveryAddress->addText('zip','Post code')
		->setMaxLength(7)
		->setHtmlId('delivery-address-zip')
		->setHtmlAttribute('placeholder','e.g. NW1 6XE')
		->setHtmlAttribute('readonly',$readOnly);
	
	if(!$readOnly){
	    $deliveryAddressZip->addConditionOn($form['isDifferentDeliveryAddress'],Form::Equal, true)->setRequired('Fill the zip input!');
	}
	
	$deliveryAddressCountry = $formDeliveryAddress->addSelect('country','Country',$countries);
	
	if(!$readOnly){
	    $deliveryAddressCountry->addConditionOn($form['isDifferentDeliveryAddress'],Form::Equal, true)->setRequired('Fill the address country input!');
	}
	
	if(!empty($deliveryAddress)){
	    $deliveryAddressStreet->setDefaultValue($deliveryAddress->getStreet());
	    $deliveryAddressCity->setDefaultValue($deliveryAddress->getCity());
	    $deliveryAddressZip->setDefaultValue($deliveryAddress->getZip());
	    $deliveryAddressCountry->setDefaultValue($deliveryAddress->getCountryISO());
	} else {
	    $deliveryAddressCountry->setDefaultValue('CZ');
	}
	
	$deliveryAddressCountry->setHtmlId('delivery-address-country')
		->setHtmlAttribute('readonly',$readOnly);
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
