<?php
declare(strict_types=1);
namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Container;

use Contributte\FormMultiplier\Multiplier;
use Nette\Forms\Controls\SubmitButton;

use App\Database\Price;
use App\Database\Product;

use Doctrine\ORM\PersistentCollection;

class PriceFormFactory {
    
    public static function createPriceForm(Form $form, \App\Presentation\Price\PricePresenter $presenter,?array $prices = null, ?Product $product = null, array $priceLists = [], ?string $priceListInternalID = null){
	$productInternalID = $form->addHidden('productInternalID')
		->setHtmlId('price-product-internal-id');;
	
	$form->addText('productCatalogueCodeSearch','Product catalogue code search')
		->setHtmlId('price-product-catalogue-code-search')
		->setOmitted();
	
	$priceListsForSelect = [];
	$defaultPriceListInternalID = null;
	foreach($priceLists as $priceList){
	    $priceListsForSelect[$priceList->getInternalID()] = $priceList->getName();
	    
	    if($priceList->getIsDefault()){
		$defaultPriceListInternalID = $priceList->getInternalID();
	    }
	}

	$multiplier = $form->addMultiplier('multiplier', function (Container $container, Form $form) use ($priceListsForSelect) {
	    $container->addHidden('internalID',null)->setHtmlAttribute('class', 'price-internal-id');
	    $container->addSelect('priceListInternalID', 'Price list', $priceListsForSelect)
	    ->setHtmlId('price-price-list');

	    $container->addText('value','Value')
		    ->setHtmlId('price-value');
	    $container->addDateTime('validFrom','Valid from')
		    ->setHtmlId('price-valid-from');
	    $container->addDateTime('validTo','Valid to')
		    ->setHtmlId('price-valid-to');
	}, 1);
       
	$multiplier->addCreateButton('Add')
        ->addOnCreateCallback(function (\Contributte\FormMultiplier\Submitter $submitter) use ($presenter) { //Submitter nebo SubmitButton?
            $submitter->onClick[] = function () use ($presenter) : void  {
                $presenter->redrawControl("dynamicPriceForm");
            };
        });
	
	$multiplier->addRemoveButton('Remove')
        ->addOnCreateCallback(function (SubmitButton  $submitter) use ($presenter) { //Submitter nebo SubmitButton?
            $submitter->onClick[] = function () use ($presenter) : void  {
                $presenter->redrawControl("dynamicPriceForm");
            };
        });
	
	if(!empty($product)){
	    $productInternalID->setDefaultValue($product->getInternalID());
	}
	
	$multiplierPrices = self::getMultiplierPrices($prices);
	
	if(!empty($multiplierPrices)){
	    $productInternalID->setDefaultValue($product->getInternalID());
	    $multiplier->setDefaults($multiplierPrices);
	}
    }
    
    private static function getMultiplierPrices(array|PersistentCollection $prices):array{
	$multiplierPrices = [];
	/** @var Price $price */
	foreach($prices as $price){
	    $multiplierPrices[] = [
		"internalID" => $price->getInternalID(),
		"priceListInternalID" => $price->getPriceList()->getInternalID(),
		"value" => $price->getValue(),
		"validFrom" => $price->getValidFrom(),
		"validTo" => $price->getValidTo()
	    ];
	}
	
	return $multiplierPrices;
    }
}
