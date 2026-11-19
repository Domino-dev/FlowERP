<?php
declare(strict_types=1);
namespace Tests\Support;

use Nette\Security\Passwords;

use App\Helpers\UUIDGenerator;

use App\Database\Company;
use App\Database\CompanyUser;
use App\Database\Customer;
use App\Database\CustomerBillingAddress;
use App\Database\Invoice;
use App\Database\InvoiceCustomer;
use App\Database\InvoiceCustomerBillingAddress;
use App\Database\InvoiceCustomerDeliveryAddress;
use App\Database\InvoiceItem;
use App\Database\PriceList;
use App\Database\Product;

/**
 * Builders of entities and form data used in tests.
 */
final class TestData {

    CONST USER_EMAIL = 'admin@test.cz';
    CONST USER_PASSWORD = 'tester123';

    public static function createCompany(): Company{
	return new Company(UUIDGenerator::generateInternalID(), 'Žluťoučký kůň s.r.o.', '+420 777 123 456', 'fakturace@zlutoucky-kun.cz', 'zlutoucky-kun.cz', '12345678', 'CZ12345678', '123456789/0100', 'Příčná 15', 'Český Krumlov', '381 01', 'CZ');
    }

    public static function createCompanyUser(bool $isEnabled = true): CompanyUser{
	$password = (new Passwords)->hash(self::USER_PASSWORD);

	return new CompanyUser(UUIDGenerator::generateInternalID(), 'Admin', null, '777123456', self::USER_EMAIL, $password, 'admin', true, $isEnabled);
    }

    public static function createPriceList(bool $isDefault = false): PriceList{
	return new PriceList(UUIDGenerator::generateInternalID(), 'Price list ' . UUIDGenerator::generateInternalID(), 'CZK', false, $isDefault);
    }

    public static function createCustomer(?PriceList $priceList = null): Customer{
	$customer = new Customer(UUIDGenerator::generateInternalID(), 'C-' . random_int(10000, 99999), 'Dominik Štěpánek', '87654321', 'CZ87654321', null, '+420608111222', 'dominik@example.cz', 14, $priceList, null, null, true);
	$customer->setCustomerBillingAddress(new CustomerBillingAddress(UUIDGenerator::generateInternalID(), $customer, 'Řeznická 1234/56', 'Ústí nad Labem', '400 01', 'Czech Republic', 'CZ'));

	return $customer;
    }

    public static function createProduct(string $name = 'Konzultace – účetní služby'): Product{
	return new Product(UUIDGenerator::generateInternalID(), 'P-' . random_int(10000, 99999), $name, 21.0, true);
    }

    /**
     * In-memory invoice (not persisted) with customer snapshot and items.
     */
    public static function createInvoice(int $itemsCount = 2, bool $withDeliveryAddress = true): Invoice{
	$invoice = new Invoice(UUIDGenerator::generateInternalID(), self::createCompanyUser(), null, self::createPriceList(), 'INV2026101', 'Bank transfer', 'Courier', 0, 0, $itemsCount, 0, 500, new \DateTimeImmutable('+14 days'), new \DateTimeImmutable('today'));

	$invoiceCustomer = new InvoiceCustomer(UUIDGenerator::generateInternalID(), 'Dominik Štěpánek', '87654321', 'CZ87654321', $invoice, '+420608111222', 'dominik@example.cz', 14);
	$invoiceCustomer->setInvoiceCustomerBillingAddress(new InvoiceCustomerBillingAddress(UUIDGenerator::generateInternalID(), $invoiceCustomer, 'Řeznická 1234/56', 'Ústí nad Labem', '400 01', 'Czech Republic', 'CZ'));
	if($withDeliveryAddress){
	    $invoiceCustomer->setInvoiceCustomerDeliveryAddress(new InvoiceCustomerDeliveryAddress(UUIDGenerator::generateInternalID(), $invoiceCustomer, 'Šafaříkova 7', 'Plzeň', '301 00', 'Czech Republic', 'CZ'));
	}
	$invoice->setInvoiceCustomer($invoiceCustomer);

	$total = 0;
	$totalWithVAT = 0;
	for($i = 1; $i <= $itemsCount; $i++){
	    $invoiceItem = new InvoiceItem(UUIDGenerator::generateInternalID(), $invoice, null, 'P-' . $i, "Školení zaměstnanců č. $i (půldenní)", 1000, 2, 10, 21, 1800, 2178);
	    $invoice->addInvoiceItem($invoiceItem);
	    $total += 1800;
	    $totalWithVAT += 2178;
	}
	$invoice->setTotal($total);
	$invoice->setTotalWithVAT($totalWithVAT);

	return $invoice;
    }

    /**
     * Data in the shape of the invoice form (InvoiceFormFactory + CustomerFormFactory).
     */
    public static function createInvoiceFormData(?PriceList $priceList, ?Customer $customer, array $products, ?string $invoiceInternalID = null, array $itemInternalIDs = []): \stdClass{
	$items = [];
	foreach(array_values($products) as $index => $product){
	    $items[] = [
		'invoiceItemInternalID' => $itemInternalIDs[$index] ?? null,
		'productInternalID' => $product->getInternalID(),
		'catalogueCode' => $product->getCatalogueCode(),
		'name' => $product->getName(),
		'priceWithoutVAT' => '1500',
		'quantity' => '3',
		'discount' => '0',
		'vatPercentageValue' => '21',
		'totalItemPriceWithoutVAT' => '4500',
		'totalItemPriceWithVAT' => '5445',
	    ];
	}

	return (object)[
	    'invoiceInternalID' => $invoiceInternalID,
	    'priceList' => $priceList?->getInternalID(),
	    'customerInternalID' => $customer?->getInternalID(),
	    'customer' => (object)[
		'name' => 'Dominik Štěpánek',
		'companyNumber' => '87654321',
		'vatNumber' => null,
		'phone' => '+420608111222',
		'email' => 'dominik@example.cz',
		'customerBillingAddress' => (object)['street' => 'Řeznická 1234/56', 'city' => 'Ústí nad Labem', 'zip' => '400 01', 'country' => 'CZ'],
		'isDifferentDeliveryAddress' => true,
		'customerDeliveryAddress' => (object)['street' => 'Šafaříkova 7', 'city' => 'Plzeň', 'zip' => '301 00', 'country' => 'CZ'],
	    ],
	    'multiplier' => $items,
	    'paymentMethod' => 'Bank transfer',
	    'deliveryMethod' => 'Courier',
	    'state' => 0,
	    'date' => new \DateTimeImmutable('today'),
	    'dueDate' => new \DateTimeImmutable('+14 days'),
	];
    }
}
