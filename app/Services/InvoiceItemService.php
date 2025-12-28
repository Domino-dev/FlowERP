<?php
declare(strict_types=1);
namespace App\Services;

use Nettrine\ORM\ManagerProvider;
use Doctrine\ORM\EntityManagerInterface;

use App\Database\Invoice;
use App\Database\InvoiceItem;
use App\Database\Product;

class InvoiceItemService {
    
    public function createInvoiceItemStructure(array $invoiceProductData, Invoice $invoice): ?InvoiceItem{
	
	$catalogueCode = $invoiceProductData['catalogueCode'];
	$name = $invoiceProductData['name'];
	$product = $invoiceProductData['product'];
	
	$price = (float)$invoiceProductData['priceWithoutVAT'];
	$quantity = (int)$invoiceProductData['quantity'];
	$vatRate = (float)$invoiceProductData['vatPercentageValue'];
	$discount = (float)$invoiceProductData['discount'];
	$totalPrice = round($price*$quantity*(1-$discount/100),2);
	$totalPriceWithVat = round($totalPrice*(1-$vatRate/100),2);
	
	return new InvoiceItem(
		    \App\Helpers\UUIDGenerator::generateInternalID(), 
		    $invoice, 
		    $product, 
		    $catalogueCode, 
		    $name, 
		    $price, 
		    $quantity, 
		    $discount, 
		    $vatRate,
		    $totalPrice,
		    $totalPriceWithVat
		);
    }
}
