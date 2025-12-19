<?php
declare(strict_types=1);
namespace App\Presentation;

use Nette\Application\UI\Presenter;

class BasePresenter extends Presenter{
    
    public function __construct() {
        
    }
    
    protected function startup(): void {
        parent::startup();
	
	if(!$this->user->loggedIn && !$this->isLinkCurrent('Sign:in')){
	    $this->redirect('Sign:in');
	}
    }
    
    public function beforeRender(): void {
	$this->template->isCompanyUserLoggedIn = $this->user->loggedIn;
	$this->template->todaysDate = date('jS \o\f F Y');
    }
    
    public function handleGetIndexPageSearch(){
	
    }
    
    public function handleSignOut(){
	$this->user->logout(true);
	$this->redirect('Sign:in');
    }
}
