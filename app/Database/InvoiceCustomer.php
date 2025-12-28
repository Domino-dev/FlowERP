<?php
declare(strict_types=1);
namespace App\Database;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use App\Database\Invoice;
use App\Database\PriceList;
use App\Database\InvoiceCustomerBillingAddress;
use App\Database\InvoiceCustomerDeliveryAddress;

use App\Database\InvoiceCustomerRepository;

#[ORM\Entity(repositoryClass: InvoiceCustomerRepository::class)]
#[ORM\Table(name:"invoices_customers",
    indexes: [
    new ORM\Index(
	name: 'IX_invoices_customers__email',
	columns: ['email']
    )]
)]
class InvoiceCustomer {
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
     #[ORM\Column(type: "string",length: 255)]
    protected string $name;
    #[ORM\Column(name: "company_number",type: "string",length: 10, nullable:true)]
    protected ?string $companyNumber;
    #[ORM\Column(name: "vat_number",type: "string",length: 14, nullable:true)]
    protected ?string $vatNumber;
    
    #[ORM\OneToOne(targetEntity: Invoice::class, inversedBy: "invoiceCustomer")]
    #[ORM\JoinColumn(name: 'invoices_id', referencedColumnName: 'id', onDelete: "CASCADE")]
    private Invoice $invoice;
    
    #[ORM\OneToOne(targetEntity: InvoiceCustomerBillingAddress::class,mappedBy: "invoiceCustomer", cascade: ["persist", "remove"])]
    private InvoiceCustomerBillingAddress $invoiceCustomerBillingAddress;
    #[ORM\OneToOne(targetEntity: InvoiceCustomerDeliveryAddress::class, mappedBy: "invoiceCustomer", cascade: ["persist", "remove"])]
    private ?InvoiceCustomerDeliveryAddress $invoiceCustomerDeliveryAddress = null;
    
    #[ORM\Column(type: "string", length: 15)]
    protected ?string $phone;
    #[ORM\Column(type: "string", length: 255)]
    protected string $email;
    #[ORM\Column(name: "due_days", type: "integer")]
    protected int $dueDays;
    #[ORM\Column(name: "last_update",type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $lastUpdate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created; 
    
    public function __construct(string $internalID, string $name, ?string $companyNumber, ?string $vatNumber, Invoice $invoice, ?string $phone, string $email, int $dueDays, ?\DateTime $lastUpdate = null, ?\DateTimeImmutable $created = null) {
	$this->internalID = $internalID;
	$this->name = $name;
	$this->companyNumber = $companyNumber;
	$this->vatNumber = $vatNumber;
	$this->invoice = $invoice;
	$this->phone = $phone;
	$this->email = $email;
	$this->dueDays = $dueDays;
	$this->lastUpdate = $lastUpdate ?? new \DateTimeImmutable();
	$this->created = $created ?? new \DateTimeImmutable();;
    }

    public function getId(): int {
	return $this->id;
    }

    public function getInternalID(): string {
	return $this->internalID;
    }

    public function getName(): string {
	return $this->name;
    }

    public function getCompanyNumber(): ?string {
	return $this->companyNumber;
    }

    public function getVatNumber(): ?string {
	return $this->vatNumber;
    }

    public function getInvoice(): Invoice {
	return $this->invoice;
    }

    public function getInvoiceCustomerBillingAddress(): InvoiceCustomerBillingAddress {
	return $this->invoiceCustomerBillingAddress;
    }

    public function getInvoiceCustomerDeliveryAddress(): ?InvoiceCustomerDeliveryAddress {
	return $this->invoiceCustomerDeliveryAddress;
    }

    public function getPhone(): ?string {
	return $this->phone;
    }

    public function getEmail(): string {
	return $this->email;
    }

    public function getDueDays(): int {
	return $this->dueDays;
    }

    public function getLastUpdate(): \DateTimeImmutable {
	return $this->lastUpdate;
    }

    public function getCreated(): \DateTimeImmutable {
	return $this->created;
    }

    public function setId(int $id): void {
	$this->id = $id;
    }

    public function setInternalID(string $internalID): void {
	$this->internalID = $internalID;
    }

    public function setName(string $name): void {
	$this->name = $name;
    }

    public function setCompanyNumber(?string $companyNumber): void {
	$this->companyNumber = $companyNumber;
    }

    public function setVatNumber(?string $vatNumber): void {
	$this->vatNumber = $vatNumber;
    }

    public function setInvoice(Invoice $invoice): void {
	$this->invoice = $invoice;
    }

    public function setInvoiceCustomerBillingAddress(InvoiceCustomerBillingAddress $invoiceCustomerBillingAddress): void {
	$this->invoiceCustomerBillingAddress = $invoiceCustomerBillingAddress;
    }

    public function setInvoiceCustomerDeliveryAddress(?InvoiceCustomerDeliveryAddress $invoiceCustomerDeliveryAddress): void {
	$this->invoiceCustomerDeliveryAddress = $invoiceCustomerDeliveryAddress;
    }

    public function setPhone(?string $phone): void {
	$this->phone = $phone;
    }

    public function setEmail(string $email): void {
	$this->email = $email;
    }

    public function setDueDays(int $dueDays): void {
	$this->dueDays = $dueDays;
    }

    public function setLastUpdate(\DateTimeImmutable $lastUpdate): void {
	$this->lastUpdate = $lastUpdate;
    }

    public function setCreated(\DateTimeImmutable $created): void {
	$this->created = $created;
    }
}
