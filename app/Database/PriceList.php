<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity(repositoryClass: PriceListRepository::class)]
#[ORM\Table(name: "`price_lists`")]
class PriceList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id',type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    #[ORM\OneToMany(mappedBy: "priceList", targetEntity: Price::class)]
    protected ?Collection $prices = null;
    #[ORM\OneToMany(mappedBy: "priceList", targetEntity: Customer::class, cascade: ["persist", "remove"])]
    private ?Collection $customers = null;
    #[ORM\Column(type: "string", length: 255, unique: true)]
    protected string $name;
    #[ORM\Column(type: "string",length: 6)]
    protected string $currency;
    #[ORM\Column(name:"is_with_vat",type: "boolean")]
    protected bool $isWithVAT;
    #[ORM\Column(name:"is_default",type: "boolean")]
    protected bool $isDefault;
    #[ORM\Column(name: "last_update",type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTime $lastUpdate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(
	    string $internalID, 
	    string $name,
	    string $currency = "CZK",
	    bool $isWithVAT = false,
	    bool $isDefault = false,
	    \DateTime $lastUpdate = new \DateTime(), 
	    \DateTimeImmutable $created = new \DateTimeImmutable()) {
	$this->internalID = $internalID;
	$this->currency = $currency;
	$this->isWithVAT = $isWithVAT;
	$this->isDefault = $isDefault;
	$this->name = $name;
	$this->lastUpdate = $lastUpdate ?? new \DateTime();
	$this->created = $created ?? new \DateTimeImmutable();
	
	$this->prices = new ArrayCollection();
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

    public function getCurrency(): string {
	return $this->currency;
    }
    
    public function getPrices(): Collection {
	return $this->prices;
    }

    public function getIsWithVAT(): bool {
	return $this->isWithVAT;
    }
    
    public function getIsDefault(): bool {
	return $this->isDefault;
    }

    public function getCustomers(): ?Collection {
	return $this->customers;
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

    public function setName(string $name): void {
	$this->name = $name;
    }

    public function setCurrency(string $currency): void {
	$this->currency = $currency;
    }
    
    public function setPrices(Collection $prices): void {
	$this->prices = $prices;
    }
    
    public function setCustomers(?Collection $customers): void {
	$this->customers = $customers;
    }
    
    public function setIsWithVAT(bool $isWithVAT): void {
	$this->isWithVAT = $isWithVAT;
    }
    
    public function setIsDefault(bool $isDefault): void {
	$this->isDefault = $isDefault;
    }
    
    public function setLastUpdate(\DateTime $lastUpdate): void {
	$this->lastUpdate = $lastUpdate;
    }

    public function setCreated(\DateTimeImmutable $created): void {
	$this->created = $created;
    }
    
    public function toArray():?array{
	return get_object_vars($this);
    }
}