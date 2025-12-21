<?php
declare(strict_types=1);
namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Container;

use App\Database\Product;

use App\Forms\PriceFormFactory;

class ProductFormFactory {
    
    public static function createProductForm(Form $form, ?Product $product = null){
	
	$productContainer = $form->addContainer('product');
	$productInternalID = $productContainer->addHidden('internalID')->setHtmlId('product-internal-id');
	
	$isEnabled = $productContainer->addCheckbox('isEnabled','Is enabled')->setHtmlId('product-is-enabled')->setDefaultValue(true);
	$catalogueCode = $productContainer->addText('catalogueCode','Catalogue code')->setHtmlId('product-catalogue-code');
	$name = $productContainer->addText('name','name')->setHtmlId('product-name');
	$vatRate = $productContainer->addText('vatRate','Vat rate')->setHtmlId('product-vat-rate')->setDefaultValue(0);
	
	if(!empty($product)){
	    $productInternalID->setDefaultValue($product->getInternalID());
	    $catalogueCode->setDefaultValue($product->getCatalogueCode());
	    $name->setDefaultValue($product->getName());
	    $vatRate->setDefaultValue($product->getVatRate());
	    $isEnabled->setDefaultValue($product->getIsEnabled());
	}
    }
}
