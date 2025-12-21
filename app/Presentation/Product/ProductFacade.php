<?php
declare(strict_types=1);
namespace App\Presentation\Product;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Application\UI\Form;

use App\Services\ProductService;

use App\Database\ProductRepository;

use App\Database\Product;

class ProductFacade {
    
    private ManagerProvider $managerProvider;
    private EntityManagerInterface $entityManagerInterface;
    
    private ProductService $productService;
    
    private ProductRepository $productRepository;
    
    public function __construct(ManagerProvider $managerProvider, ProductService $productService) {
	$this->managerProvider = $managerProvider;
	
	$this->entityManagerInterface = $this->managerProvider->getDefaultManager();
	$this->productRepository = $this->entityManagerInterface->getRepository(Product::class);
	$this->productService = $productService;
    }
    
    public function getPaginationLimit():int{
	return $this->productRepository->getPaginationLimit();
    } 
   
    public function getPaginatedProducts(int $page, string $searchSlug):?Paginator{
	
	return $this->productRepository->findPaginated($page,$searchSlug);
    } 
    
    public function getProductsCount(){
	return $this->productRepository->count();
    }
    
    public function get(?int $productID, ?string $productInternalID = null):?Product{
	
	if(empty($productID) && !empty($productInternalID)){
	    return $this->productRepository->findOneBy(['internalID' => $productInternalID]);
	}
	
	if(!empty($productID)){
	    return $this->productRepository->find($productID);
	}

	return null;
    }
    
    public function create(\stdClass $productDataRaw): ?string{
	
	$productDataRaw = $productDataRaw->product;
	$product = $this->productService->createProductStructure($productDataRaw);
	try{
	    $this->entityManagerInterface->persist($product);
	    $this->entityManagerInterface->flush();
	    return $product->getInternalID();
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $uniqueEx){
	    throw $uniqueEx;
	} catch (\Doctrine\DBAL\Exception $dcEx) {
	    bdump($dcEx->getMessage());
	} catch (Exception $ex){
	    bdump($ex->getMessage());
	} catch (Throwable $th){
	   bdump($th->getMessage());
	}
	
	return null;
    }
    
    public function update(Form $form, \stdClass $productDataRaw):bool{
	
	$productDataRaw = $productDataRaw->product;
	
	$productInternalID = $productDataRaw->internalID;
	if(empty($productInternalID)){
	    return false;
	}
	
	/** @var Product $product */
	$product = $this->productRepository->findOneBy(['internalID' => $productInternalID]);
	if(empty($product)){
	    return false;
	}
	
	$product->setCatalogueCode($productDataRaw->catalogueCode);
	$product->setName($productDataRaw->name);
	$product->setVatRate((float)$productDataRaw->vatRate);
	$product->setIsEnabled($productDataRaw->isEnabled);
	
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
    
    public function delete(Product $product){
	if(empty($product)){
	    return false;
	}
	
	try {
	    $this->entityManagerInterface->remove($product);
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
