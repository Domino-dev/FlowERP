<?php
declare(strict_types=1);
namespace App\Presentation\Price;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Application\UI\Form;

use App\Services\PriceService;
use App\Services\ProductService;

use App\Database\PriceRepository;
use App\Database\PriceListRepository;
use App\Database\ProductRepository;

use App\Database\Price;
use App\Database\Product;
use App\Database\PriceList;

class PriceFacade {
    
    CONST PAGE = 1;
    CONST LIMIT = 10;
    
    private ManagerProvider $managerProvider;
    private EntityManagerInterface $entityManagerInterface;
    
    private PriceService $priceService;
    private ProductService $productService;
    
    private PriceRepository $priceRepository;
    private PriceListRepository $priceListRepository;
    private ProductRepository $productRepository;
    
    public function __construct(ManagerProvider $managerProvider, PriceService $priceService, ProductService $productService) {
	$this->managerProvider = $managerProvider;
	$this->entityManagerInterface = $this->managerProvider->getDefaultManager();
	$this->priceRepository = $this->entityManagerInterface->getRepository(Price::class);
	$this->productRepository = $this->entityManagerInterface->getRepository(Product::class);;
	$this->priceListRepository = $this->entityManagerInterface->getRepository(PriceList::class);
	$this->priceService = $priceService;
	$this->productService = $productService;
    }
    
    public function getLimit():int{
	return $this->priceRepository->getFindPaginatedLimit();
    }
    
    public function getPricesCount():int{
	
	return $this->priceRepository->count();
    }
    
    public function getPaginatedPrices(int $page,string $searchSlug):?Paginator{
	
	return $this->priceRepository->findPaginated($page,$searchSlug);
    } 
    
    public function getPriceListsForSelect():?array{
	
	return $this->priceListRepository->findAll();
    }
    
    public function get(?int $priceID, ?string $priceInternalID = null):?Price{
	
	if(empty($priceID) && !empty($priceInternalID)){
	    return $this->priceRepository->findOneBy(['internalID' => $priceInternalID]);
	}
	
	if(!empty($priceID)){
	    return $this->priceRepository->find($priceID);
	}

	return null;
    }
    
    public function getPricesByProduct(string $productInternalID):?array{
	
	$product = $this->productRepository->findOneBy(['internalID' => $productInternalID]);
	
	if(empty($product)){
	    return null;
	}
	
	return $this->priceRepository->findBy(['product' => $product]);	
    }
    
    public function create(\stdClass $pricesDataRaw, Form $form): ?string{
	
	if(empty($pricesDataRaw->productInternalID)){
	    return false;
	}
	
	$product = $this->productRepository->findOneBy(['internalID' => $pricesDataRaw->productInternalID]);
	
	$prices = [];
	foreach($pricesDataRaw->multiplier as $priceDataRaw){
	    $priceList = $this->priceListRepository->findOneBy(['internalID' => $priceDataRaw->priceListInternalID]);
	    if(empty($product) || empty($priceList)){
		return false;
	    }
	    
	    $price = $this->priceService->createPriceStructure($priceDataRaw,$priceList,$product);
	    if(empty($price)){
		return false;
	    }
	    
	    $prices[] = $price;
	}
	
	foreach($prices as $price){
	    $this->entityManagerInterface->persist($price);
	}
	    try{
		$this->entityManagerInterface->flush();
		return $product->getInternalID();
	    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
		// LOG
		throw $ex;
	    } catch (\Doctrine\DBAL\Exception $ex) {
		// LOG
	    } catch (Exception $ex){
		// LOG
	    } catch (Throwable $ex){
		// LOG
	    }
	
	return null;
    }
    
    public function update(Form $form, \stdClass $pricesDataRaw):bool{
	
	if(empty($pricesDataRaw->productInternalID)){
	    return false;
	}
	
	$product = $this->productRepository->findOneBy(['internalID' => $pricesDataRaw->productInternalID]);
	
	foreach($pricesDataRaw->multiplier as $priceDataRaw){
	    $priceList = $this->priceListRepository->findOneBy(['internalID' => $priceDataRaw->priceListInternalID]);
	    if(empty($product) || empty($priceList)){
		return false;
	    }

	    $priceInternalID = $priceDataRaw->internalID;
	    if(empty($priceInternalID)){
		return false;
	    }

	    /** @var \App\Database\Price $price */
	    $price = $this->priceRepository->findOneBy(['internalID' => $priceInternalID]);
	    if(empty($price)){
		return false;
	    }

	    $price->setPriceList($priceList);
	    $price->setValue((float)$priceDataRaw->value);
	    $price->setValidFrom($priceDataRaw->validFrom);
	    $price->setValidTo($priceDataRaw->validTo);
	}
	
	try{
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx){
	    // LOG
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    // LOG
	} catch (Exception $ex){
	    // LOG
	} catch (Throwable $th){
	    // LOG
	}
	
	return false;
    }
    
    public function delete(?array $prices){
	if(empty($prices)){
	    return false;
	}
	
	foreach($prices as $price){
	    $this->entityManagerInterface->remove($price);
	}
	
	try {
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
    
    public function getProductAutocomplete(string $slug):?string{
	
	return $this->productService->getProductAutocomplete($slug);
    }
    
    public function getPriceListInternalIDByID(int $priceListID):?string{
	
	return $this->priceListRepository->findOneBy(['id' => $priceListID])?->getInternalID();
    }
    
    public function getPriceListByInternalID(string $priceListInternalID):?PriceList{
	
	return $this->priceListRepository->findOneBy(['internalID' => $priceListInternalID]);
    }
    
    public function getProductByInternalID(string $productInternalID):?Product{
	
	return $this->productRepository->findOneBy(['internalID' => $productInternalID]);
    }
}
