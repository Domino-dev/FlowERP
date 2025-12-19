<?php
declare(strict_types=1);
namespace App\Security;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;

use Nette\Security\Passwords;

use App\Database\CompanyUser;

use App\Database\CompanyUserRepository;

class Authenticator implements \Nette\Security\Authenticator{
    
    private EntityManagerInterface $entityManagerProvider;
    
    private Passwords $passwords;
    
    private CompanyUserRepository $companyUserRepository;
    
    public function __construct(ManagerProvider $managerProvider, Passwords $passwords) {
	$this->entityManagerProvider = $managerProvider->getDefaultManager();
	$this->companyUserRepository = $this->entityManagerProvider->getRepository(CompanyUser::class);
	$this->passwords = $passwords;
    }
    
    #[\Override]
    public function authenticate(string $username, string $password): \Nette\Security\IIdentity {
	
	if(!filter_var($username, FILTER_VALIDATE_EMAIL)){
	    throw new \Nette\Security\AuthenticationException('Wrong credentials!');
	}
	
	/** @var CompanyUser $companyUser */
	$companyUser = $this->companyUserRepository->findOneBy(['email' => $username,'isEnabled' => true]);
	if(empty($companyUser)){
	    throw new \Nette\Security\AuthenticationException('Wrong credentials!');
	}
	
	if(!$this->passwords->verify($password, $companyUser->getPassword())){
	    throw new \Nette\Security\AuthenticationException('Wrong credentials!');
	}
	
	return new \Nette\Security\SimpleIdentity(
		$companyUser->getInternalID(), 
		[$companyUser->getRole()]
		);
    }
}
