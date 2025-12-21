<?php
declare(strict_types=1);
namespace App\Services;

use App\Database\PriceList;

class PriceListService {
    //put your code here
    
    public function createPriceListStructure(\stdClass $priceListDataRaw, \Nette\Application\UI\Form $form){
	
	$currencies = $this->getCurrencies();
	$currencyISO = $currencies[$priceListDataRaw->currency];
	
	return new PriceList(
		\App\Helpers\UUIDGenerator::generateInternalID(), 
		$priceListDataRaw->name, 
		$currencyISO, 
		$priceListDataRaw->isWithVAT,
		$priceListDataRaw->isDefault);
    }
    
    private function getCurrencies():?array{
	$path = '../App/Resources/currencies.json';
	$currenciesJsonString = file_get_contents($path);
	$currencies = json_decode($currenciesJsonString, true);
	
	if(empty($currencies)){
	    return [];
	}
	
	$currenciesISO = array_keys($currencies) ?? [];
	sort($currenciesISO, SORT_STRING);
	$currenciesISO = array_combine($currenciesISO, $currenciesISO);
	
	return $currenciesISO;
    }
}
