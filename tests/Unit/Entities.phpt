<?php
/**
 * Test: entity behaviour (constructors, collections).
 */
declare(strict_types=1);

use Tester\Assert;

use App\Helpers\UUIDGenerator;

use App\Database\InvoiceCustomer;
use App\Database\InvoiceCustomerDeliveryAddress;
use App\Database\InvoiceItem;
use App\Database\Price;

use Tests\Support\TestData;

require __DIR__ . '/../bootstrap.php';

test('invoice delivery address keeps its country', function () {
    // regression: the constructor did not assign country -> invoice could not be saved
    $invoice = TestData::createInvoice(0, false);
    $invoiceCustomer = $invoice->getInvoiceCustomer();

    $deliveryAddress = new InvoiceCustomerDeliveryAddress(UUIDGenerator::generateInternalID(), $invoiceCustomer, 'Šafaříkova 7', 'Plzeň', '301 00', 'Czech Republic', 'CZ');

    Assert::same('Czech Republic', $deliveryAddress->getCountry());
    Assert::same('CZ', $deliveryAddress->getCountryISO());
});

test('syncItems removes missing items and adds new ones', function () {
    $invoice = TestData::createInvoice(3);
    [$first, $second] = $invoice->getInvoiceItems()->toArray();
    $new = new InvoiceItem(UUIDGenerator::generateInternalID(), $invoice, null, 'P-new', 'New item', 100, 1, 0, 21, 100, 121);

    $invoice->syncItems([$first, $second, $new]);

    $items = $invoice->getInvoiceItems()->toArray();
    Assert::count(3, $items);
    Assert::contains($first, $items);
    Assert::contains($second, $items);
    Assert::contains($new, $items);
    Assert::same($invoice, $new->getInvoice());
});

test('addInvoiceItem does not add the same item twice', function () {
    $invoice = TestData::createInvoice(1);
    $item = $invoice->getInvoiceItems()->first();

    $invoice->addInvoiceItem($item);

    Assert::count(1, $invoice->getInvoiceItems());
});

test('product addPrice links the price to the product once', function () {
    $product = TestData::createProduct();
    $product->setPrice(new \Doctrine\Common\Collections\ArrayCollection());
    $price = new Price(UUIDGenerator::generateInternalID(), $product, TestData::createPriceList(), 100, new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 year'));

    $product->addPrice($price);
    $product->addPrice($price);

    Assert::count(1, $product->getPrice());
    Assert::same($product, $price->getProduct());

    $product->removePrice($price);
    Assert::count(0, $product->getPrice());
});

test('invoice status name', function () {
    $invoice = TestData::createInvoice(0);

    $invoice->setStatus(500);
    Assert::same('issued', $invoice->getStatusName());

    $invoice->setStatus(1000);
    Assert::same('paid', $invoice->getStatusName());
});
