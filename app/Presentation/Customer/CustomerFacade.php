<?php
declare(strict_types=1);
namespace App\Presentation\Customer;

use Exception;
use Doctrine\ORM\EntityManagerInterface;
use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Database\CustomerRepository;
use App\Database\PriceListRepository;

use App\Services\CustomerService;

use App\Database\Customer;
use App\Database\CustomerBillingAddress;
use App\Database\CustomerDeliveryAddress;
use App\Database\PriceList;

/**
 * Description of CustomerFacade
 *
 * @author stepa
 */
final class CustomerFacade {
    
    CONST CUSTOMER_DEF_ROLE = 'customer';
    CONST PAGE = 1;
    CONST LIMIT = 2;
    
    private EntityManagerInterface $entityManagerInterface;
    
    private CustomerService $customerService;
    private CustomerRepository $customerRepository;
    
    private PriceListRepository $priceListRepository;
    
    public function __construct(
	    ManagerProvider $managerProvider,
	    CustomerService $customerService) {
	$this->customerService = $customerService;
	$this->entityManagerInterface = $managerProvider->getDefaultManager();
	$this->customerRepository = $this->entityManagerInterface->getRepository(Customer::class);
	$this->priceListRepository = $this->entityManagerInterface->getRepository(PriceList::class);
    }
    
    // CREATE
    public function createCustomer(\stdClass $customerFormDataRaw):?string{
	$customerDataRaw = $customerFormDataRaw->customer;
	
	$priceList = $this->priceListRepository->findOneBy(['internalID' => $customerDataRaw->priceList]);
	
	$customer = $this->customerService->createCustomerStructure($customerDataRaw,$priceList);
	
	$customerBillingAddressDataRaw = $customerDataRaw->customerBillingAddress;
	$customerBillingAddress = $this->customerService->createCustomerBillingAddressSctructure($customerBillingAddressDataRaw,$customer);
	$customer->setCustomerBillingAddress($customerBillingAddress);
	
	$customerDeliveryAddressDataRaw = $customerDataRaw->customerDeliveryAddress;
	if(!empty($customerDeliveryAddressDataRaw->city)){
	    $customerDeliveryAddress = $this->customerService->createCustomerDeliveryAddressSctructure($customerDeliveryAddressDataRaw,$customer);
	    $customer->setCustomerDeliveryAddress($customerDeliveryAddress);
	}
	
	try{
	    $this->entityManagerInterface->persist($customer);
	    $this->entityManagerInterface->flush();
	    return $customer->getInternalID();
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx){
	    // LOG
	    throw $uniqueEx;
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $th){
	    // LOG
	}
	
	return null;
    }
    
    public function generateCustomerIdentificator(){
	return $this->customerService->generateCustomerIdentificator();
    }
    
    // READ
    public function getLimit():int{
	return $this->customerRepository->getPagiantionLimit();
    }
    
    public function getPaginatedCustomers(int $page = 1, string $searchSlug = ''):?Paginator{
	return $this->customerRepository->findPaginated($page,$searchSlug,self::LIMIT) ?? [];
    }
    
    public function getCustomerByInternalID(string $customerInternalID): Customer{
	return $this->customerRepository->findOneBy(['internalID' => $customerInternalID]);
    }
    
    public function getPriceLists():?array{
	return $this->priceListRepository->findAll();
    }
    
    public function getCustomersCnt(){
	return $this->customerRepository->count();
    }
    
    public function checkIdentificatorUniqueness(string $identificator):bool{
	$customerRow = $this->customerRepository->findOneBy(['identificator' => $identificator]);
	
	return !empty($customerRow);
    }
    
    // UPDATE
    
    public function updateCustomer(\stdClass $customerFormDataRaw):bool{
	$customerDataRaw = $customerFormDataRaw->customer;
	$customerInternalID = $customerDataRaw->customerInternalID;
	if(empty($customerInternalID)){
	    return false;
	}
	
	$priceList = $this->priceListRepository->findOneBy(['internalID' => $customerDataRaw->priceList]);
	
	$customerBillingAddressDataRaw = $customerDataRaw->customerBillingAddress;
	$customerDeliveryAddressDataRaw = $customerDataRaw->customerDeliveryAddress;
	
	/** @var Customer $customer */
	$customer = $this->customerRepository->findOneBy(['internalID' => $customerInternalID]);
	if(empty($customer)){
	    return false;
	}
	
	/** @var CustomerBillingAddress $customerBillingAddress */
	$customerBillingAddress = $customer->getCustomerBillingAddress();
	if(empty($customerBillingAddress)){
	    return false;
	}
	
	/** @var CustomerDeliveryAddress $customerDeliveryAddress */
	$customerDeliveryAddress = $customer->getCustomerDeliveryAddress();
	
	$customer->setIsEnabled($customerDataRaw->isEnabled);
	$customer->setPriceList($priceList);
	$customer->setName($customerDataRaw->name);
	$customer->setCompanyNumber($customerDataRaw->companyNumber);
	$customer->setVatNumber($customerDataRaw->vatNumber);
	$customer->setNote($customerDataRaw->note);
	$customer->setPhone((string) $customerDataRaw->phone);
	$customer->setEmail($customerDataRaw->email);
	$customer->setDueDays($customerDataRaw->dueDays ?? 0);
	
	if(isset($customerDataRaw->customerAutocomplete->masterInternalID) && !empty($customerDataRaw->customerAutocomplete->masterInternalID)){
	    /** @var Customer $customerData */
	    $masterInternalID = $customerDataRaw->customerAutocomplete->masterInternalID;
	    $customerData = $this->customerRepository->findOneBy(['internalID' => $masterInternalID]);
	    $customer->setMasterID($customerData);
	}
	
	$billingAddressCountry = $this->customerService->findCountryByISO($customerBillingAddressDataRaw->country);
	$customerBillingAddress->setStreet($customerBillingAddressDataRaw->street);
	$customerBillingAddress->setCity($customerBillingAddressDataRaw->city);
	$customerBillingAddress->setZip($customerBillingAddressDataRaw->zip);
	$customerBillingAddress->setCountry($billingAddressCountry);
	$customerBillingAddress->setCountryISO($customerBillingAddressDataRaw->country);
	
	if(isset($customerDeliveryAddress) && !empty($customerDeliveryAddressDataRaw?->street)){
	    $deliveryAddressCountry = $this->customerService->findCountryByISO($customerDeliveryAddressDataRaw->country);
	    $customerDeliveryAddress->setStreet($customerDeliveryAddressDataRaw->street);
	    $customerDeliveryAddress->setCity($customerDeliveryAddressDataRaw->city);
	    $customerDeliveryAddress->setZip($customerDeliveryAddressDataRaw->zip);
	    $customerDeliveryAddress->setCountry($deliveryAddressCountry);
	    $customerDeliveryAddress->setCountryISO($customerDeliveryAddressDataRaw->country);
	} else if(!empty($customerDeliveryAddressDataRaw?->street)){
	    $customerDeliveryAddress = $this->customerService->createCustomerDeliveryAddressSctructure($customerDeliveryAddressDataRaw,$customer);
	    $customer->setCustomerDeliveryAddress($customerDeliveryAddress);
	}
	
	try{
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx){
	    // LOG
	    throw $uniqueEx;
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $th){
	    // LOG
	}
	
	return false;
    }
    
    // DELETE
    public function deleteCustomer(Customer $customer):bool{
	if(empty($customer)){
	    return false;
	}
	
	try {
	    $this->entityManagerInterface->remove($customer);
	    $this->entityManagerInterface->flush();
	    return true;
	}catch (\Doctrine\DBAL\Exception $dcEx) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $th){
	    // LOG
	}
	
	return false;
    }
}
