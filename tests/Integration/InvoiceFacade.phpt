<?php
/**
 * Test: invoice flows against the test database.
 */
declare(strict_types=1);

use Tester\Assert;

use App\Presentation\Invoice\InvoiceFacade;
use App\Presentation\Customer\CustomerFacade;
use App\Presentation\PriceList\PriceListFacade;

use App\Database\CompanyUser;
use App\Database\Customer;
use App\Database\Invoice;
use App\Database\InvoiceItem;
use App\Database\PriceList;
use App\Database\Product;

use Tests\Support\DatabaseTestCase;
use Tests\Support\TestData;

require __DIR__ . '/../bootstrap.php';

class InvoiceFacadeTest extends DatabaseTestCase {

    private InvoiceFacade $invoiceFacade;

    private CompanyUser $companyUser;
    private PriceList $priceList;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void {
	parent::setUp();

	$this->invoiceFacade = $this->container->getByType(InvoiceFacade::class);

	$this->companyUser = TestData::createCompanyUser();
	$this->priceList = TestData::createPriceList(true);
	$this->customer = TestData::createCustomer($this->priceList);
	$this->product = TestData::createProduct();
	$this->persist($this->companyUser, $this->priceList, $this->customer, $this->product);
    }

    private function createInvoice(): Invoice{
	$invoiceData = TestData::createInvoiceFormData($this->priceList, $this->customer, [$this->product]);
	$invoiceInternalID = $this->invoiceFacade->createInvoice($invoiceData, $this->companyUser->getInternalID());
	Assert::type('string', $invoiceInternalID);

	$this->clear();
	return $this->invoiceFacade->getInvoice($invoiceInternalID);
    }

    public function testCreateInvoice(): void {
	$invoice = $this->createInvoice();

	Assert::same('Dominik Štěpánek', $invoice->getInvoiceCustomer()->getName());
	Assert::same('Plzeň', $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getCity());
	Assert::same('Czech Republic', $invoice->getInvoiceCustomer()->getInvoiceCustomerDeliveryAddress()->getCountry());
	Assert::count(1, $invoice->getInvoiceItems());
	Assert::same(4500.0, (float)$invoice->getTotal());
	Assert::same(5445.0, (float)$invoice->getTotalWithVAT());
    }

    public function testInvoiceNumbersAreUnique(): void {
	$first = $this->createInvoice();
	$second = $this->createInvoice();

	Assert::notSame($first->getNumber(), $second->getNumber());
    }

    public function testCreateInvoiceWithoutCustomerAndPriceList(): void {
	// falls back to the default price list
	$invoiceData = TestData::createInvoiceFormData(null, null, [$this->product]);

	$invoiceInternalID = $this->invoiceFacade->createInvoice($invoiceData, $this->companyUser->getInternalID());
	$this->clear();
	$invoice = $this->invoiceFacade->getInvoice($invoiceInternalID);

	Assert::null($invoice->getCustomer());
	Assert::same($this->priceList->getInternalID(), $invoice->getPriceList()->getInternalID());
    }

    public function testCreateInvoiceWithUnknownUserFails(): void {
	$invoiceData = TestData::createInvoiceFormData($this->priceList, $this->customer, [$this->product]);

	Assert::null($this->invoiceFacade->createInvoice($invoiceData, 'unknown'));
    }

    public function testUpdateInvoiceSavesVatNumber(): void {
	$invoice = $this->createInvoice();
	$itemInternalID = $invoice->getInvoiceItems()->first()->getInternalID();

	$invoiceData = TestData::createInvoiceFormData($this->priceList, $this->customer, [$this->product], $invoice->getInternalID(), [$itemInternalID]);
	$invoiceData->customer->vatNumber = 'CZ87654321';

	Assert::true($this->invoiceFacade->updateInvoice($invoiceData));
	$this->clear();

	$invoice = $this->invoiceFacade->getInvoice($invoice->getInternalID());
	Assert::same('CZ87654321', $invoice->getInvoiceCustomer()->getVatNumber());
	Assert::count(1, $invoice->getInvoiceItems());
    }

    public function testDeletingProductKeepsInvoiceItems(): void {
	$invoice = $this->createInvoice();

	$product = $this->entityManagerInterface->find(Product::class, $this->product->getId());
	$this->entityManagerInterface->remove($product);
	$this->entityManagerInterface->flush();
	$this->clear();

	$invoiceItems = $this->entityManagerInterface->getRepository(InvoiceItem::class)->findAll();
	Assert::count(1, $invoiceItems);
	Assert::null($invoiceItems[0]->getProduct());
	Assert::same($this->product->getName(), $invoiceItems[0]->getName());
    }

    public function testDeletingCustomerKeepsInvoice(): void {
	$invoice = $this->createInvoice();
	$customerFacade = $this->container->getByType(CustomerFacade::class);

	Assert::true($customerFacade->deleteCustomer($customerFacade->getCustomerByInternalID($this->customer->getInternalID())));
	$this->clear();

	$invoice = $this->invoiceFacade->getInvoice($invoice->getInternalID());
	Assert::notNull($invoice);
	Assert::null($invoice->getCustomer());
	Assert::same('Dominik Štěpánek', $invoice->getInvoiceCustomer()->getName());
    }

    public function testPriceListUsedOnInvoiceCannotBeDeleted(): void {
	$this->createInvoice();
	$priceListFacade = $this->container->getByType(PriceListFacade::class);
	$priceList = $priceListFacade->get(null, $this->priceList->getInternalID());

	Assert::exception(
	    fn() => $priceListFacade->deletePriceList($priceList),
	    \Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException::class
	);
    }

    public function testDeletingPriceListKeepsCustomers(): void {
	// regression: price list used to cascade remove its customers
	$priceListFacade = $this->container->getByType(PriceListFacade::class);

	Assert::true($priceListFacade->deletePriceList($priceListFacade->get(null, $this->priceList->getInternalID())));
	$this->clear();

	$customer = $this->entityManagerInterface->getRepository(Customer::class)->findOneBy(['internalID' => $this->customer->getInternalID()]);
	Assert::notNull($customer);
	Assert::null($customer->getPriceList());
    }
}

(new InvoiceFacadeTest)->run();
