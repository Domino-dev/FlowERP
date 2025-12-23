<?php
declare(strict_types=1);
namespace App\Services;

use App\Database\PriceList;
use App\Database\Product;
use App\Database\Price;

class PriceService {
    
    
    public function createPriceStructure(\stdClass $priceDataRaw, PriceList $priceList, Product $product):?Price{
	return new Price(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		$product, 
		$priceList, 
		(float)$priceDataRaw->value, 
		$priceDataRaw->validFrom, 
		$priceDataRaw->validTo
		);
    }
}
