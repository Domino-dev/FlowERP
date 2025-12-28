<?php
namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Contributte\FormMultiplier\Multiplier;
use Nette\Forms\Controls\SubmitButton;

use App\Database\Invoice;

use Doctrine\ORM\PersistentCollection;

class InvoiceFormFactory {
    
    CONST INVOICE_STATES = [0 => 'draft',500 => 'issued',900 => 'cancelled',1000 => 'paid'];
    
    public static function createInvoiceForm(
	    Container|Form $form, 
	    \App\Presentation\Invoice\InvoicePresenter $presenter,
	    ?Invoice $invoice = null, 
	    array $paymentMethods = [],
	    array $priceLists = [],
	    ?string $paymentMethodDefaultInternalID = null,
	    ?string $priceListDefaultInternalID = null){
	
	$invoiceInternalID = $form->addHidden('invoiceInternalID')->setHtmlId('invoice-internal-id');
	$customerInternalID = $form->addHidden('customerInternalID')->setHtmlId('customer-internal-id');
	$isPriceListWithVAT = $form->addHidden('isPriceListWithVAT')->setHtmlId('is-price-list-with-vat');
	
	$invoiceDate = $form->addDate('date','Invoice date')->setDefaultValue(date('Y-m-d'));
	$invoiceDueDate = $form->addDate('dueDate','Due date')->setHtmlId('invoice-due-date');
	$invoiceStatus = $form->addSelect('state','Invocie state',self::INVOICE_STATES);
	
	$priceListsForSelect = [];
	foreach($priceLists as $priceList){
	    $priceListsForSelect[$priceList->getInternalID()] = $priceList->getName();

	    if($priceList->getIsDefault() && empty($priceListDefaultInternalID)){
		$priceListDefaultInternalID = $priceList->getInternalID();
	    }
	}
	
	$priceList = $form->addSelect('priceList','Price list',$priceListsForSelect)->setHtmlId('invoice-price-list-internal-id');
	
	$paymentMethod = $form->addText('paymentMethod','Payment method')->setHtmlId('invoice-payment-method');
	$form->addText('customerAutocomplete','Customer autocomplete')->setOmitted();
	
	$multiplier = $form->addMultiplier('multiplier', function (Container $container, Form $form) {
	    $container->addHidden('productID',null)->setHtmlAttribute('class', 'item-internal-id');
	    $container->addText('catalogueCode','Catalogue code')
		    ->setRequired()
		    ->setHtmlAttribute('class','item-catalogue-code');
	    $container->addText('name','name')
		    ->setRequired()
		    ->setHtmlAttribute('class','item-name');
	    $container->addText('priceWithoutVAT','Price per piece (without VAT)')
		    ->setRequired()
		    ->setHtmlAttribute('class','item-price-without-VAT')
		    ->setDefaultValue(0);
	    $container->addText('vatPercentageValue','VAT')
		    ->setRequired()
		    ->setHtmlAttribute('class','item-vat-percentage')
		    ->setDefaultValue(0);
	    $container->addText('discount','discount')
		    ->setRequired()
		    ->setHtmlAttribute('class','item-discount')
		    ->setDefaultValue(0);
	    $container->addText('quantity','quantity')
		    ->setRequired()
		    ->setHtmlAttribute('class','item-quantity')
		    ->setHtmlAttribute('type','number')
		    ->setDefaultValue(1);
	    $container->addText('productPriceWithoutVAT','Product price without VAT')
		    ->setRequired()
		    ->setHtmlAttribute('class','product-price-without-VAT-total-val')
		    ->setHtmlAttribute('type','number')
		    ->setHtmlAttribute('readonly',true)
		    ->setDefaultValue(0);
	    $container->addText('productPriceWithVAT','Product price with VAT')
		    ->setRequired()
		    ->setHtmlAttribute('class','product-price-with-VAT-total-val')
		    ->setHtmlAttribute('type','number')
		    ->setHtmlAttribute('readonly',true)
		    ->setDefaultValue(0);
	}, 1);

	$multiplier->addCreateButton('Add')
        ->addOnCreateCallback(function (\Contributte\FormMultiplier\Submitter $submitter) use ($presenter) { //Submitter nebo SubmitButton?
            $submitter->onClick[] = function () use ($presenter) : void  {
                $presenter->redrawControl("dynamicInvoiceForm");
            };
        });
	
	$multiplier->addRemoveButton('Remove')
        ->addOnCreateCallback(function (SubmitButton  $submitter) use ($presenter) { //Submitter nebo SubmitButton?
            $submitter->onClick[] = function () use ($presenter) : void  {
                $presenter->redrawControl("dynamicInvoiceForm");
            };
        });
	
	if(!empty($invoice)){
	    
	    $invoiceItems = self::getMultiplierItems($invoice->getInvoiceItems());
	    
	    $invoiceInternalID->setDefaultValue($invoice->getInternalID());
	    $customerInternalID->setDefaultValue($invoice->getCustomer()->getInternalID());
	    
	    $priceList->setDefaultValue($priceListDefaultInternalID);
	    $invoiceDate->setDefaultValue($invoice->getDocumentDate());
	    $invoiceDueDate->setDefaultValue($invoice->getDueDate());
	    $invoiceStatus->setDefaultValue($invoice->getStatus());
	    
	    $multiplier->setDefaults($invoiceItems);
	}
    }
    
    private static function getMultiplierItems(array|PersistentCollection $items):array{
	$invoiceItems = [];
	foreach($items as $invoiceItem){
	    $invoiceItems[] = [
		"productID" => $invoiceItem->getProduct()->getInternalID(),
		"catalogueCode" => $invoiceItem->getCatalogueCode(),
		"name" => $invoiceItem->getName(),
		"value" => $invoiceItem->getPrice(),
		"quantity" => $invoiceItem->getQuantity()
	    ];
	}
	
	return $invoiceItems;
    }
}
