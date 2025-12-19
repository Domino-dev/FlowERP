<?php
declare(strict_types=1);
namespace App\Presentation\Sign;

use Nette\Application\UI\Form;

use App\Security\Authenticator;

class SignPresenter extends \App\Presentation\BasePresenter{
    
    private Authenticator $authenticator;
    
    public function __construct(Authenticator $authenticator) {
	$this->authenticator = $authenticator;
    }
    
    public function inAction(){
	
    }
    
    public function inRender(){
	
    }
    
    public function createComponentSignInForm(): ?Form {
	$form = new Form();
	
	\App\Forms\SignFormFactory::createSignForm($form);
	
	$form->addSubmit('login','Login');
	$form->onSuccess[] = [$this,'signInFormSuccess'];
	
	return $form;
    }
    
    public function signInFormSuccess(Form $form, \stdClass $data): void{
	
	$companyUserIdentity = $this->authenticator->authenticate($data->sign->email, $data->sign->password);
	if(empty($companyUserIdentity)){
	    $this->flashMessage('Invalid credentials!');
	}
	
	try {
	    $this->user->login($companyUserIdentity);
	} catch (\Nette\Security\AuthenticationException $authEx) {
	    $this->flashMessage('Something went wrong!');
	    $this->redirect('this');
	}
	
	$this->redirect('Home:default');
    }
}
