<?php
declare(strict_types=1);
namespace App\Forms;

use Nette\Application\UI\Form;
use App\Database\Company;

class CompanyFormFactory {
    
    
    public static function createCompanyForm(Form $form, ?Company $company){
	
	$countries = \App\Helpers\Country::getCountries();
	
        $name = $form->addText('name','name')->setRequired('Fill the company name input!');
        $phone = $form->addText('phone','phone')->setRequired('Fill the phone input!');
        $email = $form->addText('email','email')->setRequired('Fill the email input!');
        $webDomain = $form->addText('webDomain','webDomain')->setRequired('Fill the web domain input!');
        $companyNumber = $form->addText('companyNumber','companyNumber');
        $vatNumber = $form->addText('vatNumber','vatNumber');
        $bankNumber = $form->addText('bankNumber','bankNumber');
        $street = $form->addText('street','street')->setRequired('Fill the street input!');
        $city = $form->addText('city','city')->setRequired('Fill the city input!');
        $zip = $form->addText('zip','zip')->setRequired('Fill the zip input!');
        $country = $form->addSelect('country','country',$countries)->setRequired('Chose country!');
	
	if(!empty($company)){
	    $name->setDefaultValue($company->getName());
	    $phone->setDefaultValue($company->getPhone());
	    $email->setDefaultValue($company->getEmail());
	    $webDomain->setDefaultValue($company->getWebDomain());
	    $companyNumber->setDefaultValue($company->getCompanyNumber());
	    $vatNumber->setDefaultValue($company->getVatNumber());
	    $bankNumber->setDefaultValue($company->getBankNumber());
	    $street->setDefaultValue($company->getStreet());
	    $city->setDefaultValue($company->getCity());
	    $zip->setDefaultValue($company->getZip());
	    $country->setDefaultValue($company->getCountryISO());
	}
    }
}