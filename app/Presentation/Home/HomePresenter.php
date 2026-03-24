<?php
declare(strict_types=1);
namespace App\Presentation\Home;

use Nette;

use App\Presentation\BasePresenterFacade;


final class HomePresenter extends \App\Presentation\BasePresenter{
    
    public function __construct(BasePresenterFacade $basePresenterFacade) {
	parent::__construct($basePresenterFacade);
    }
}
