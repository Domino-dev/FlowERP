<?php
namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Container;

class SignFormFactory {
    
    public static function createSignForm(Form $form){
	
	$signContainer = $form->addContainer('sign');
	
	$signContainer->addEmail('email','Email')->setHtmlId('sign-email')->setRequired();
	$signContainer->addPassword('password','Password')->setHtmlId('sign-password')->setRequired();
    }
}
