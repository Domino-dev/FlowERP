<?php
declare(strict_types=1);
namespace App\Services;

use Nette\Utils\Html;
use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;

use App\Helpers\UUIDGenerator;

use App\Database\Product;

use App\Database\ProductRepository;

class ProductService {
    
    private EntityManagerInterface $entityManagerInterface;
    
    private ProductRepository $productRepository;
    
    public function __construct(ManagerProvider $managerProvider) {
	$this->entityManagerInterface = $managerProvider->getDefaultManager();
	$this->productRepository = $this->entityManagerInterface->getRepository(Product::class);
    }
    
    public function createProductStructure(\stdClass $priceListDataRaw): Product{
	
	return new Product(
		UUIDGenerator::generateInternalID(), 
		$priceListDataRaw->catalogueCode, 
		$priceListDataRaw->name, 
		(float)$priceListDataRaw->vatRate, 
		$priceListDataRaw->isEnabled);
    }
    
    public function getProductAutocomplete(string $slug):?string{
	
	$products = $this->productRepository->findMultipleBySlug($slug);
	if(empty($products)){
	    return null;
	}
	
	$productHtml = "";
	foreach ($products as $product) {
	    $el = Html::el('p')
		->addText($product->getCatalogueCode() . ' | ' . $product->getName())
		->addAttributes([
		    'class' => 'product-autocomplete-row',
		    'data-product-id' => $product->getInternalID(),
		    'data-product-catalogue-code' => $product->getCatalogueCode(),
		    'data-product-name' => $product->getName(),
		]);

	    $productHtml .= $el->toHtml();
	}

	return $productHtml;
    }
}
