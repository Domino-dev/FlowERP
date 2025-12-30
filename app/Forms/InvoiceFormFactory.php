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
	$invoiceCustomerInternalID = $form->addHidden('invoiceCustomerInternalID')->setHtmlId('invoice-customer-internal-id');
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
	    $container->addHidden('invoiceItemInternalID',null)->setHtmlAttribute('class', 'invoice-item-internal-id');
	    $container->addHidden('productInternalID',null)->setHtmlAttribute('class', 'product-internal-id');
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
	    $container->addText('totalItemPriceWithoutVAT','Total item price without VAT')
		    ->setRequired()
		    ->setHtmlAttribute('class','total-item-price-without-VAT')
		    ->setHtmlAttribute('type','number')
		    ->setHtmlAttribute('readonly',true)
		    ->setDefaultValue(0);
	    $container->addText('totalItemPriceWithVAT','Total item price with VAT')
		    ->setRequired()
		    ->setHtmlAttribute('class','total-item-price-with-VAT')
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
	    $invoiceCustomerInternalID->setDefaultValue($invoice->getInvoiceCustomer()->getInternalID());
	    
	    if(!empty($invoice->getPriceList())){
		$isPriceListWithVAT->setDefaultValue($invoice->getPriceList()->getIsWithVAT());
	    }
	    
	    $priceList->setDefaultValue($priceListDefaultInternalID);
	    $invoiceDate->setDefaultValue($invoice->getDocumentDate());
	    $invoiceDueDate->setDefaultValue($invoice->getDueDate());
	    $invoiceStatus->setDefaultValue($invoice->getStatus());
	    $paymentMethod->setDefaultValue($invoice->getPaymentMethod());
	    
	    $multiplier->setDefaults($invoiceItems);
	}
    }
    
    private static function getMultiplierItems(array|PersistentCollection $items):array{
	$invoiceItems = [];
	/** @var \App\Database\InvoiceItem $invoiceItem */
	foreach($items as $invoiceItem){
	    $invoiceItems[] = [
		'invoiceItemInternalID' => $invoiceItem->getInternalID(),
		'productInternalID' => $invoiceItem->getProduct()->getInternalID(),
		'catalogueCode' => $invoiceItem->getCatalogueCode(),
		'name' => $invoiceItem->getName(),
		'priceWithoutVAT' => $invoiceItem->getPrice(),
		'vatPercentageValue' => $invoiceItem->getVATRateValue(),
		'discount' => $invoiceItem->getDiscount(),
		'quantity' => $invoiceItem->getQuantity(),
		'totalItemPriceWithoutVAT' => $invoiceItem->getTotalPrice(),
		'totalItemPriceWithVAT' => $invoiceItem->getTotalPriceWithVAT()
	    ];
	}
	
	return $invoiceItems;
    }
}
