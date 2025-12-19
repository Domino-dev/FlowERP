<?php
declare(strict_types=1);
namespace App\Forms;

use Nette\Application\UI\Form;
use App\Database\CompanyUser;

class CompanyUserFormFactory {
    
    CONST ROLES = ['admin','economy','technic'];
    
    public static function createCompanyUserForm(Form $form, ?CompanyUser $companyUser){
        $customerID = $form->addHidden('internalID')->setHtmlId('company-user-internal-id');
	
	$isEnabled = $form->addCheckbox('isEnabled','Is enabled')->setHtmlId('company-user-is-enabled')->setDefaultValue(true);
	$name = $form->addText('name','Name')->setHtmlId('company-user-first-name')->setHtmlId('company-user-name')->setHtmlAttribute('placeholder','e.g. John Doe');
	$note = $form->addTextArea('note','Customer note')->setHtmlId('company-user-note')->setHtmlAttribute('placeholder','Optional')->setHtmlAttribute('rows',5);
      
        $email = $form->addEmail('email','Email')->setRequired()->setHtmlId('company-user-email')->setHtmlId('company-user-name')->setHtmlAttribute('placeholder','e.g. john.doe@gmail.com');
        $phone = $form->addInteger('phone','Phone')->setHtmlType('tel')->setHtmlId('company-user-phone')->setHtmlAttribute('placeholder','e.g. 020 7561 1106');
	
	$password = $form->addPassword('password','Password')->setHtmlAttribute('placeholder','********')->setHtmlId('company-user-password');
	$roles = $form->addSelect('roles','Roles',self::ROLES)->setHtmlId('company-user-role');
	
	if(!empty($companyUser)){
	    $rolesItems = $roles->items;
	    
	    $roleIndex = array_search($companyUser->getRole(), $rolesItems);
	    
	    $customerID->setDefaultValue($companyUser->getInternalID());
	    $isEnabled->setDefaultValue($companyUser->getIsEnabled());
	    $name->setDefaultValue($companyUser->getName());
	    $note->setDefaultValue($companyUser->getNote());
	    $email->setDefaultValue($companyUser->getEmail());
	    $phone->setDefaultValue($companyUser->getPhone());
	    $roles->setDefaultValue($roleIndex);
	} else{
	    $password->setRequired();
	}
    }
}
