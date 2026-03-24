<?php
declare(strict_types=1);
namespace App\Presentation;

use Nette\Application\UI\Presenter;

use App\Presentation\BasePresenterFacade;

class BasePresenter extends Presenter{
    
    private BasePresenterFacade $basePresenterFacade;
    
    public function __construct(BasePresenterFacade $basePresenterFacade) {
        $this->basePresenterFacade = $basePresenterFacade;
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
	$this->template->companyInternalID = $this->basePresenterFacade->getCompanyInternalID();
    }
    
    public function handleGetIndexPageSearch(){
	
    }
    
    public function handleSignOut(){
	$this->user->logout(true);
	$this->redirect('Sign:in');
    }
}
