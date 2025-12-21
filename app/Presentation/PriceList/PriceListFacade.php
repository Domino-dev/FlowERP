<?php
declare(strict_types=1);

namespace App\Presentation\PriceList;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Application\UI\Form;

use App\Services\PriceListService;

use App\Database\PriceListRepository;

use App\Database\PriceList;

class PriceListFacade {
    
    private PriceListService $priceListService;
    
    private ManagerProvider $managerProvider;
    private EntityManagerInterface $entityManagerInterface;
    
    private PriceListRepository $priceListRepository;
    
    public function __construct(ManagerProvider $managerProvider, PriceListService $priceListService) {
	$this->managerProvider = $managerProvider;
	$this->entityManagerInterface = $this->managerProvider->getDefaultManager();
	$this->priceListRepository = $this->entityManagerInterface->getRepository(PriceList::class);
	$this->priceListService = $priceListService;
    }
    
    public function createPriceList(Form $form, \stdClass $priceListDataRaw):?string{	
	
	$defaultPriceList = $this->priceListRepository->getDefaultPriceList();
	if(!empty($defaultPriceList) && $priceListDataRaw->priceList->isDefault){
	    // LOG
	    return null;
	}
	
	$priceList = $this->priceListService->createPriceListStructure($priceListDataRaw->priceList, $form);
	
	try{
	    $this->entityManagerInterface->persist($priceList);
	    $this->entityManagerInterface->flush();
	    return $priceList->getInternalID();
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
    
    public function getTotalPriceListsCount():int{
	return $this->priceListRepository->count();
    }
    
    public function getPaginatedCustomers(int $page = 1, string $searchSlug = ''):?Paginator{
	return $this->priceListRepository->findPaginated($page,$searchSlug) ?? [];
    }
    
    public function getPaginatedPriceListsLimit():int{
	return $this->priceListRepository->getPaginatedLimit();
    }
    
    public function get(?int $priceListID, ?string $priceListInternalID = null):?PriceList{
	
	if(empty($priceListID) && !empty($priceListInternalID)){
	    $priceList = $this->priceListRepository->findOneBy(['internalID' => $priceListInternalID]);
	    
	    if(!empty($priceList)){
		$priceListID = $priceList->getId();
	    }
	}
	
	if(!empty($priceListID)){
	    return $this->priceListRepository->find($priceListID);
	}

	return null;
    }
    
    public function updatePriceList(Form $form, \stdClass $priceListDataRaw):bool{

	$priceListInternalID = $priceListDataRaw->priceListInternalID;
	if(empty($priceListInternalID)){
	    return false;
	}
	
	/** @var PriceList $priceList */
	$priceList = $this->priceListRepository->findOneBy(['internalID' => $priceListInternalID]);
	if(empty($priceList)){
	    return false;
	}
	
	$priceList->setName($priceListDataRaw->name);
	$priceList->setCurrency($priceListDataRaw->currency);
	$priceList->setIsWithVAT($priceListDataRaw->isWithVAT);
	
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
    
    public function deletePriceList(PriceList $priceList):bool{
	
	try {
	    $this->entityManagerInterface->remove($priceList);
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $th){
	    // LOG
	}
	
	return false;
    }
}
