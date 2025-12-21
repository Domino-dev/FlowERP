<?php
declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;

use App\Database\PriceList;

class PriceListFormFactory {
    
    public static function createPriceListForm(Form $form, ?PriceList $priceList){
	
	$priceListContainer = $form->addContainer('priceList');
	$priceListInternalID = $priceListContainer->addHidden('internalID')->setHtmlId('price-list-internal-id');
	$isDefault = $priceListContainer->addCheckbox('isDefault','Is default')->setHtmlId('price-list-is-default')->setRequired();
	$name = $priceListContainer->addText('name','Name')->setHtmlId('price-list-name')->setRequired();
	$currency = $priceListContainer->addSelect('currency','Currency', self::getCurrencies())->setHtmlId('price-list-currency')->setRequired();
	$isWithVAT = $priceListContainer->addCheckbox('isWithVAT','Is with vat')->setHtmlId('price-list-is-with-VAT');
	
	if(!empty($priceList)){
	    $priceListInternalID->setDefaultValue($priceList->getInternalID());
	    $isDefault->setDefaultValue($priceList->getIsDefault());
	    $name->setDefaultValue($priceList->getName());
	    $currency->setDefaultValue($priceList->getCurrency());
	    $isWithVAT->setDefaultValue($priceList->getIsWithVAT());
	}
    }
    
    private static function getCurrencies():?array{
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
