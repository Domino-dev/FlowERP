<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\Common\Collections\Collection;

use App\Database\PriceList;
use App\Database\Invoice;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: "`customers`",
	indexes: [
	new \Doctrine\ORM\Mapping\Index(
	    name: 'IX_customers__email_is_enabled',
	    columns: ['email', 'is_enabled']
	)]
)]
class Customer{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    #[ORM\Column(type: "string", unique: true)]
    protected string $identificator;
    #[ORM\Column(type: "string",)]
    protected string $name;
    #[ORM\Column(name: "company_number",type: "string",length: 50, nullable:true)]
    protected ?string $companyNumber;
    #[ORM\Column(name: "vat_number",type: "string",length: 20, nullable:true)]
    protected ?string $vatNumber;
    #[ORM\Column(type: "text",nullable: true)]
    protected ?string $note;
    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: "customer")]
    #[ORM\JoinColumn(name: "price_lists_id", referencedColumnName: "id", nullable: true, onDelete:"SET NULL")]
    private ?PriceList $priceList = null;
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: "customer", cascade: ["persist", "remove"])]
    private ?Collection $invoice = null;
    #[ORM\OneToOne(mappedBy: "customer", targetEntity: CustomerBillingAddress::class, cascade: ["persist", "remove"])]
    private ?CustomerBillingAddress $customerBillingAddress = null;
    #[ORM\OneToOne(mappedBy: "customer", targetEntity: CustomerDeliveryAddress::class, cascade: ["persist", "remove"])]
    private ?CustomerDeliveryAddress $customerDeliveryAddress = null;
    #[ORM\Column(type: "string", length: 15)]
    protected ?string $phone;
    #[ORM\Column(type: "string")]
    protected string $email;
    #[ORM\Column(name: "due_days", type: "integer", options:["default" => 0])]
    protected int $dueDays;
    #[ORM\Column(name:"is_enabled",type: "boolean", options: ["default" => true])]
    protected bool $isEnabled;
    #[ORM\Column(name: "last_update",type: "datetime", options: ["default" => "CURRENT_TIMESTAMP", "other" => "ON UPDATE CURRENT_TIMESTAMP"])]
    protected \DateTime $lastUpdate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created; 
    
    public function __construct(string $internalID, string $identificator, string $name, ?string $companyNumber, ?string $vatNumber, ?string $note, ?string $phone, string $email, int $dueDays, ?PriceList $priceList, ?CustomerBillingAddress $customerBillingAddress, ?CustomerDeliveryAddress $customerDeliveryAddress = null, bool $isEnabled = false,?string $lastUpdate = null) {
	$this->internalID = $internalID;
	$this->identificator = $identificator;
	$this->name = $name;
	$this->companyNumber = $companyNumber;
	$this->vatNumber = $vatNumber;
	$this->note = $note;
	$this->phone = $phone;
	$this->email = $email;
	$this->dueDays = $dueDays;
	$this->priceList = $priceList;
	$this->customerBillingAddress = $customerBillingAddress;
	$this->customerDeliveryAddress = $customerDeliveryAddress;
	$this->isEnabled = $isEnabled;
	$this->lastUpdate = new \DateTime();
	$this->created = new \DateTimeImmutable();
	
	$this->invoice = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): int {
	return $this->id;
    }
    
    public function getInternalID(): string {
	return $this->internalID;
    }
    
    public function getIdentificator(): string {
	return $this->identificator;
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
    
    public function getInvoice(): ?Collection {
	return $this->invoice;
    }
    
    public function getCustomerBillingAddress(): ?CustomerBillingAddress {
	return $this->customerBillingAddress;
    }

    public function getCustomerDeliveryAddress(): ?CustomerDeliveryAddress {
	return $this->customerDeliveryAddress;
    }
    
    public function getNote(): ?string {
	return $this->note;
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
    
    public function getPriceList(): ?PriceList {
	return $this->priceList;
    }

    public function getIsEnabled(): bool {
	return $this->isEnabled;
    }

    public function getLastUpdate(): string {
	return $this->lastUpdate;
    }

    public function getCreated(): string {
	return $this->created;
    }

    public function setId(int $id): void {
	$this->id = $id;
    }

    public function setInternalID(string $internalID): void {
	$this->internalID = $internalID;
    }

    public function setIdentificator(string $identificator): void {
	$this->identificator = $identificator;
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

    public function setNote(?string $note): void {
	$this->note = $note;
    }

    public function setInvoice(?Collection $invoice): void {
	$this->invoice = $invoice;
    }
    
    public function setCustomerBillingAddress(?CustomerBillingAddress $customerBillingAddress): void {
	$this->customerBillingAddress = $customerBillingAddress;
    }
    
    public function setCustomerDeliveryAddress(?CustomerDeliveryAddress $customerDeliveryAddress): void {
	$this->customerDeliveryAddress = $customerDeliveryAddress;
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

    public function setPriceList(?PriceList $priceList): void {
	$this->priceList = $priceList;
    }

    public function setIsEnabled(bool $isEnabled): void {
	$this->isEnabled = $isEnabled;
    }

    public function setLastUpdate(string $lastUpdate): void {
	$this->lastUpdate = $lastUpdate;
    }

    public function setCreated(string $created): void {
	$this->created = $created;
    }
    
    public function addInvoice(Invoice $invoice): void{
	if($this->invoice->contains($invoice)){
	    $this->invoice->add($invoice);
	    $invoice->setCustomer($this);
	}
    }
    
    public function toArray():?array{
	return get_object_vars($this);
    }
}