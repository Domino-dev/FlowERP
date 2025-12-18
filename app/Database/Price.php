<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;

use App\Database\Product;
use App\Database\PriceList;

#[ORM\Entity(repositoryClass: PriceRepository::class)]
#[ORM\Table(name:'`prices`',
	uniqueConstraints: [
        new \Doctrine\ORM\Mapping\UniqueConstraint(
            name: 'UQ_products_id__pricelists_id__valid',
            columns: ['products_id', 'price_lists_id','valid_from','valid_to']
        )
])]
class Price {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\Column(name: 'internal_id', type: 'string', length: 36, options: ['fixed' => true ], unique: true)]
    protected string $internalID;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'products_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Product $product;

    #[ORM\ManyToOne(targetEntity: PriceList::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'price_lists_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected PriceList $priceList;
    
    #[ORM\Column(type:'decimal', precision:10, scale: 2)]
    protected float $value;

    #[ORM\Column(name: 'valid_from', type: 'datetime_immutable')]
    protected \DateTimeImmutable $validFrom;
    
    #[ORM\Column(name: 'valid_to', type: 'datetime_immutable')]
    protected \DateTimeImmutable $validTo;
    
    #[ORM\Column(name: 'last_update', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP', 'other' => 'ON UPDATE CURRENT_TIMESTAMP'])]
    protected \DateTime $lastUpdate;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected \DateTimeImmutable $created;
    
    public function __construct(string $internalID, Product $product, PriceList $priceList, float $value, \DateTimeImmutable $validFrom, \DateTimeImmutable $validTo, ?\DateTime $lastUpdate = null, ?\DateTimeImmutable $created = null) {
	$this->internalID = $internalID;
	$this->product = $product;
	$this->priceList = $priceList;
	$this->value = $value;
	$this->validFrom = $validFrom;
	$this->validTo = $validTo;
	$this->lastUpdate = $lastUpdate ?? new \DateTime();
	$this->created = $created ?? new \DateTimeImmutable();
    }

    public function getId(): int {
	return $this->id;
    }

    public function getInternalID(): string {
	return $this->internalID;
    }

    public function getProduct(): Product {
	return $this->product;
    }

    public function getPriceList(): PriceList {
	return $this->priceList;
    }

    public function getValue(): float {
	return $this->value;
    }

    public function getValidFrom(): \DateTimeImmutable {
	return $this->validFrom;
    }

    public function getValidTo(): \DateTimeImmutable {
	return $this->validTo;
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

    public function setProduct(Product $product): void {
	$this->product = $product;
    }

    public function setPriceList(PriceList $priceList): void {
	$this->priceList = $priceList;
    }

    public function setValue(float $value): void {
	$this->value = $value;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): void {
	$this->validFrom = $validFrom;
    }

    public function setValidTo(\DateTimeImmutable $validTo): void {
	$this->validTo = $validTo;
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
