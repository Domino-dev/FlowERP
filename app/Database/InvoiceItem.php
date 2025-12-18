<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;

use App\Database\Product;
use App\Database\Invoice;

use App\Database\InvoiceitemRepository;

#[ORM\Entity(repositoryClass: InvoiceitemRepository::class)]
#[ORM\Table(name:"invoices_items",
	indexes: [
	new ORM\Index(
	    name: 'IX_invoices_customers__email',
	    columns: ['email']
	)]
)]
class InvoiceItem {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    
    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy:"invoiceItem")]
    #[ORM\JoinColumn(name: "invoices_id", referencedColumnName: "id", onDelete: "CASCADE")]
    protected Invoice $invoice;
    
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "invoiceItem")]
    #[ORM\JoinColumn(name: "products_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
    protected ?Product $product;
    
    #[ORM\Column(name: "catalogue_code",type: "string", length:255)]
    protected string $catalogueCode;
    #[ORM\Column(name: "name",type: "string", length:255)]
    protected string $name;
    #[ORM\Column(name: "unit_price", type:"decimal", precision:10, scale: 2)]
    protected float $price;
    #[ORM\Column(type:"integer")]
    protected int $quantity;
    #[ORM\Column(type:"decimal", precision:10, scale: 2)]
    protected float $discount;
    #[ORM\Column(name: 'vat_rate_value',type:"decimal", precision:10, scale: 2)]
    protected float $VATRateValue;
    #[ORM\Column(name: "total_price",type:"decimal", precision:10, scale: 2)]
    protected float $totalPrice;
    #[ORM\Column(name: "total_price_with_vat",type:"decimal", precision:10, scale: 2)]
    protected float $totalPriceWithVAT;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(string $internalID, Invoice $invoice, ?Product $product, string $catalogueCode, string $name, float $price, int $quantity, float $discount, float $VATRateValue, float $totalPrice, ?\DateTimeImmutable $created = null) {
	$this->internalID = $internalID;
	$this->invoice = $invoice;
	$this->product = $product;
	$this->catalogueCode = $catalogueCode;
	$this->name = $name;
	$this->price = $price;
	$this->quantity = $quantity;
	$this->discount = $discount;
	$this->VATRateValue = $VATRateValue;
	$this->totalPrice = $totalPrice;
	$this->created = $created ?? new \DateTimeImmutable();
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

    public function getInvoice(): Invoice {
	return $this->invoice;
    }

    public function getProduct(): ?Product {
	return $this->product;
    }

    public function getCatalogueCode(): string {
	return $this->catalogueCode;
    }

    public function getName(): string {
	return $this->name;
    }

    public function getPrice(): float {
	return $this->price;
    }

    public function getQuantity(): int {
	return $this->quantity;
    }

    public function getDiscount(): float {
	return $this->discount;
    }

    public function getVATRateValue(): float {
	return $this->VATRateValue;
    }
    
    public function getTotalPrice(): float {
	return $this->totalPrice;
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

    public function setInvoice(Invoice $invoice): void {
	$this->invoice = $invoice;
    }

    public function setProduct(?Product $product): void {
	$this->product = $product;
    }

    public function setCatalogueCode(string $catalogueCode): void {
	$this->catalogueCode = $catalogueCode;
    }

    public function setName(string $name): void {
	$this->name = $name;
    }

    public function setPrice(float $price): void {
	$this->price = $price;
    }

    public function setQuantity(int $quantity): void {
	$this->quantity = $quantity;
    }

    public function setDiscount(float $discount): void {
	$this->discount = $discount;
    }

    public function setVATRateValue(float $VATRateValue): void {
	$this->VATRateValue = $VATRateValue;
    }
    
    public function setTotalPrice(float $totalPrice): void {
	$this->totalPrice = $totalPrice;
    }

    public function setCreated(\DateTimeImmutable $created): void {
	$this->created = $created;
    }
}
