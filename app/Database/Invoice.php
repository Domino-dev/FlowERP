<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use App\Database\Customer;
use App\Database\InvoiceCustomer;
use App\Database\InvoiceItem;
use App\Database\PriceList;
use App\Database\CompanyUser;

use App\Database\InvoiceRepository;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name:"invoices",
	indexes: [
	new ORM\Index(
	    name: 'IX_invoices__number',
	    columns: ['number']
	)]
)]
class Invoice {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    
    #[ORM\ManyToOne(targetEntity: CompanyUser::class, inversedBy:"invoice")]
    #[ORM\JoinColumn(name: "company_users_id", referencedColumnName: "id", onDelete:"RESTRICT")]
    protected CompanyUser $companyUser;
    
    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy:"invoice")]
    #[ORM\JoinColumn(name: "customers_id", referencedColumnName: "id", nullable: true, onDelete:"CASCADE")]
    protected Customer $customer;
    
    #[ORM\OneToOne(targetEntity: InvoiceCustomer::class, mappedBy:"invoice", cascade: ["persist", "remove"])]
    protected InvoiceCustomer $invoiceCustomer;
    
    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy:"invoice")]
    #[ORM\JoinColumn(name: "price_lists_id", referencedColumnName: "id", nullable: true, onDelete:"CASCADE")]
    protected ?PriceList $priceList = null;
    
    #[ORM\OneToMany(targetEntity: InvoiceItem::class, mappedBy:"invoice", cascade: ["persist", "remove"])]
    protected ?Collection $invoiceItems = null;
    
    #[ORM\Column(name: "number",type:"string",length: 32)]
    protected string $number;
    #[ORM\Column(type:"decimal", precision:10, scale: 2)]
    protected float $total;
    #[ORM\Column(name: 'total_with_vat', type:"decimal", precision:10, scale: 2)]
    protected float $totalWithVAT;
    #[ORM\Column(name: "products_cnt",type:"integer")]
    protected int $productsCount;
    #[ORM\Column(type:"decimal", precision:2)]
    protected float $discount;
    #[ORM\Column(type: "integer", options: ["default" => 0])]
    protected int $status;
    #[ORM\Column(type: "datetime_immutable", name:"due_date", options: ["default" => "CURRENT_TIMESTAMP"], nullable:true)]
    protected ?\DateTimeImmutable $dueDate = null;
    #[ORM\Column(type: "datetime_immutable", name:"document_date", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $documentDate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    
    public function __construct(string $internalID, CompanyUser $companyUser, Customer $customer, ?PriceList $priceList, string $number, float $total, float $totalWithVAT, int $productsCount, float $discount, int $status, ?\DateTimeImmutable $dueDate, \DateTimeImmutable $documentDate, ?\DateTimeImmutable $created = null) {
	$this->internalID = $internalID;
	$this->companyUser = $companyUser;
	$this->customer = $customer;
	$this->priceList = $priceList;
	$this->number = $number;
	$this->total = $total;
	$this->totalWithVAT = $totalWithVAT;
	$this->productsCount = $productsCount;
	$this->discount = $discount;
	$this->status = $status;
	$this->dueDate = $dueDate;
	$this->documentDate = $documentDate;
	$this->created = $created ?? new \DateTimeImmutable();
	
	$this->invoiceItems = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /*public function __toString() {
        return $this->internalID;
    }*/
    
    public function getId(): int {
	return $this->id;
    }

    public function getInternalID(): string {
	return $this->internalID;
    }

    public function getCompanyUser(): CompanyUser {
	return $this->companyUser;
    }

    public function getCustomer(): Customer {
	return $this->customer;
    }
    
    public function getInvoiceCustomer(): InvoiceCustomer {
	return $this->invoiceCustomer;
    }
    
    public function getPriceList(): ?PriceList {
	return $this->priceList;
    }
    
    public function getInvoiceItems(): ?Collection {
	return $this->invoiceItems;
    }

    public function getNumber(): string {
	return $this->number;
    }

    public function getTotal(): float {
	return $this->total;
    }

    public function getTotalWithVAT(): float {
	return $this->totalWithVAT;
    }
    
    public function getProductsCount(): int {
	return $this->productsCount;
    }

    public function getDiscount(): float {
	return $this->discount;
    }

    public function getStatus(): int {
	return $this->status;
    }

    public function getDueDate(): ?\DateTimeImmutable {
	return $this->dueDate;
    }

    public function getDocumentDate(): \DateTimeImmutable {
	return $this->documentDate;
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

    public function setCompanyUser(CompanyUser $companyUser): void {
	$this->companyUser = $companyUser;
    }

    public function setCustomer(Customer $customer): void {
	$this->customer = $customer;
    }

    public function setInvoiceCustomer(InvoiceCustomer $invoiceCustomer): void {
	$this->invoiceCustomer = $invoiceCustomer;
    }
    
    public function setPriceList(?PriceList $priceList): void {
	$this->priceList = $priceList;
    }
    
    public function setInvoiceItems(?Collection $invoiceItems): void {
	$this->invoiceItems = $invoiceItems;
    }

    public function setNumber(string $number): void {
	$this->number = $number;
    }

    public function setTotal(float $total): void {
	$this->total = $total;
    }
    
    public function setTotalWithVAT(float $totalWithVAT): void {
	$this->totalWithVAT = $totalWithVAT;
    }
    
    public function setProductsCount(int $productsCount): void {
	$this->productsCount = $productsCount;
    }

    public function setDiscount(float $discount): void {
	$this->discount = $discount;
    }

    public function setStatus(int $status): void {
	$this->status = $status;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): void {
	$this->dueDate = $dueDate;
    }

    public function setDocumentDate(\DateTimeImmutable $documentDate): void {
	$this->documentDate = $documentDate;
    }

    public function setCreated(\DateTimeImmutable $created): void {
	$this->created = $created;
    }
    
    public function addInvoiceItem(InvoiceItem $invoiceItem){
	if(!$this->invoiceItems->contains($invoiceItem)){
	    $this->invoiceItems->add($invoiceItem);
	    $invoiceItem->setInvoice($this);
	}
    }
    
    public function syncItems(array $newItems): void{
	
	foreach ($this->invoiceItems as $oldItem) {
	    if (!in_array($oldItem, $newItems, true)) {
		$this->removeItem($oldItem);
	    }
	}
	
	foreach ($newItems as $item) {
	    if (!$this->invoiceItems->contains($item)) {
		$this->addItem($item);
	    }
	}
    }
}
