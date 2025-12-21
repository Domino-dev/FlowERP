<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use App\Database\Price;

use App\Database\ProductRepository;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name:"`products`")]
class Product {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    #[ORM\OneToMany(mappedBy: "product", targetEntity: Price::class, cascade: ["persist", "remove"])]
    protected Collection $price;
    #[ORM\Column(name: "catalogue_code",type: "string", length:255, unique: true)]
    protected string $catalogueCode;
    #[ORM\Column(name: "name",type: "string", length:255)]
    protected string $name;
    #[ORM\Column(name: "vat_rate",type:'decimal', precision:10, scale: 2)]
    protected float $vatRate;
    #[ORM\Column(name: "is_enabled",type: "boolean", options: ["default" => true])]
    protected bool $isEnabled;
    #[ORM\Column(name: "last_update",type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTime $lastUpdate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(
	    string $internalID, 
	    string $catalogueCode, 
	    string $name, 
	    float $vatRate,
	    bool $isEnabled, 
	    ?\DateTime $lastUpdate = null, 
	    ?\DateTimeImmutable $created = null) {
	$this->internalID = $internalID;
	$this->catalogueCode = $catalogueCode;
	$this->name = $name;
	$this->vatRate = $vatRate;
	$this->isEnabled = $isEnabled;
	$this->lastUpdate = $lastUpdate ?? new \DateTime();
	$this->created = $created ?? new \DateTimeImmutable();
    }

    
    public function getId(): int {
	return $this->id;
    }

    public function getInternalID(): string {
	return $this->internalID;
    }

    public function getPrice(): Collection {
	return $this->price;
    }
    
    public function getCatalogueCode(): string {
	return $this->catalogueCode;
    }

    public function getName(): string {
	return $this->name;
    }

    public function getVatRate(): float {
	return $this->vatRate;
    }
    
    public function getIsEnabled(): bool {
	return $this->isEnabled;
    }

    public function getLastUpdate(): \DateTime {
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

    public function setCatalogueCode(string $catalogueCode): void {
	$this->catalogueCode = $catalogueCode;
    }

    public function setName(string $name): void {
	$this->name = $name;
    }

    public function setVatRate(float $vatRate): void {
	$this->vatRate = $vatRate;
    }
    
    public function setIsEnabled(bool $isEnabled): void {
	$this->isEnabled = $isEnabled;
    }

    public function setLastUpdate(\DateTime $lastUpdate): void {
	$this->lastUpdate = $lastUpdate;
    }

    public function setCreated(\DateTimeImmutable $created): void {
	$this->created = $created;
    }
    
    public function setPrice(Collection $price): void {
	$this->price = $price;
    }
    
    public function toArray():?array{
	return get_object_vars($this);
    }
}
