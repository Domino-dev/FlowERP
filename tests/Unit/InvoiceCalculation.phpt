<?php
/**
 * Test: invoice item and invoice sum calculations.
 */
declare(strict_types=1);

use Tester\Assert;

use App\Services\InvoiceItemService;
use App\Services\InvoiceService;

use Tests\Support\TestData;

require __DIR__ . '/../bootstrap.php';

$invoiceItemService = new InvoiceItemService();

test('item total price is price * quantity minus discount', function () use ($invoiceItemService) {
    Assert::same(300.0, $invoiceItemService->calculateInvoiceItemTotalPrice(100, 3));
    Assert::same(270.0, $invoiceItemService->calculateInvoiceItemTotalPrice(100, 3, 10));
    Assert::same(0.0, $invoiceItemService->calculateInvoiceItemTotalPrice(100, 3, 100));
});

test('item total price is rounded to 2 decimals', function () use ($invoiceItemService) {
    Assert::same(33.33, $invoiceItemService->calculateInvoiceItemTotalPrice(11.111, 3));
});

test('item total price with VAT adds VAT', function () use ($invoiceItemService) {
    Assert::same(363.0, $invoiceItemService->calculateInvoiceItemTotalPriceWithVAT(300, 21));
    Assert::same(300.0, $invoiceItemService->calculateInvoiceItemTotalPriceWithVAT(300, 0));
    Assert::same(12.1, $invoiceItemService->calculateInvoiceItemTotalPriceWithVAT(10, 21));
});

test('item structure is created from form data', function () use ($invoiceItemService) {
    $invoice = TestData::createInvoice(0);
    $product = TestData::createProduct();

    $invoiceItem = $invoiceItemService->createInvoiceItemStructure([
	'catalogueCode' => 'P-1',
	'name' => 'Školení',
	'product' => $product,
	'priceWithoutVAT' => '1000',
	'quantity' => '2',
	'vatPercentageValue' => '21',
	'discount' => '10',
    ], $invoice);

    Assert::same($invoice, $invoiceItem->getInvoice());
    Assert::same($product, $invoiceItem->getProduct());
    Assert::same('Školení', $invoiceItem->getName());
    Assert::same(1800.0, $invoiceItem->getTotalPrice());
    Assert::same(2178.0, $invoiceItem->getTotalPriceWithVAT());
});

test('invoice sum with VAT is higher than without VAT', function () {
    // regression: VAT used to be subtracted (total * (1 - vat))
    $invoiceService = createContainer()->getByType(InvoiceService::class);

    $sums = $invoiceService->getInvoiceItemsSumAndCnt([
	['priceWithoutVAT' => '1500', 'quantity' => '3', 'vatPercentageValue' => '21', 'discount' => '0'],
	['priceWithoutVAT' => '12500', 'quantity' => '1', 'vatPercentageValue' => '21', 'discount' => '10'],
    ]);

    Assert::same(15750.0, $sums['sumWithoutVAT']);
    Assert::same(19057.5, $sums['sumWithVAT']);
    Assert::same(2, $sums['cnt']);
});
