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
	$totalPrice = $this->calculateInvoiceItemTotalPrice($price,$quantity,$discount);
	$totalPriceWithVat = $this->calculateInvoiceItemTotalPriceWithVAT($totalPrice, $vatRate);
	
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
    
    public function calculateInvoiceItemTotalPrice(float $price, int $quantity, float $discount = 0){
	return round($price*$quantity*(1-$discount/100),2);
    }
    
    public function calculateInvoiceItemTotalPriceWithVAT(float $totalPrice, float $vatRate = 0){
	return round($totalPrice*(1+$vatRate/100),2);
    }
}
