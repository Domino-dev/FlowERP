<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use App\Database\InvoiceCustomer;
use App\Database\InvoiceCustomerDeliveryAddressRepository;

#[ORM\Entity(repositoryClass: InvoiceCustomerDeliveryAddressRepository::class)]
#[ORM\Table(name:"invoices_customers_delivery_addresses")]
class InvoiceCustomerDeliveryAddress {
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    #[ORM\OneToOne(inversedBy: "invoiceCustomerDeliveryAddress",targetEntity: InvoiceCustomer::class)]
    #[ORM\JoinColumn(name: "invoice_customers_id", referencedColumnName: "id", onDelete: "CASCADE")]
    protected InvoiceCustomer $invoiceCustomer;
    #[ORM\Column(type: "string")]
    protected ?string $street;
    #[ORM\Column(type: "string")]
    protected ?string $city;
    #[ORM\Column(type: "string")]
    protected ?string $zip;
    #[ORM\Column(type: "string")]
    protected string $country;
    #[ORM\Column(name: 'country_iso',type: "string")]
    protected string $countryISO;
    #[ORM\Column(name:"last_update",type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP", "other" => "ON UPDATE CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $lastUpdate;
    #[ORM\Column(type: "datetime_immutable",options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(string $internalID, InvoiceCustomer $invoiceCustomer, ?string $street, ?string $city, ?string $zip, string $country, string $countryISO, ?\DateTimeImmutable $lastUpdate = null, ?\DateTimeImmutable $created = null) {
	$this->internalID = $internalID;
	$this->invoiceCustomer = $invoiceCustomer;
	$this->street = $street;
	$this->city = $city;
	$this->zip = $zip;
	$this->lastUpdate = $lastUpdate ?? new \DateTimeImmutable();
	$this->created = $created ?? new \DateTimeImmutable();
    }

    public function getId(): int {
	return $this->id;
    }

    public function getInternalID(): string {
	return $this->internalID;
    }

    public function getInvoiceCustomer(): InvoiceCustomer {
	return $this->invoiceCustomer;
    }

    public function getStreet(): ?string {
	return $this->street;
    }

    public function getCity(): ?string {
	return $this->city;
    }

    public function getZip(): ?string {
	return $this->zip;
    }

    public function getCountry(): string {
	return $this->country;
    }

    public function getCountryISO(): string {
	return $this->countryISO;
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

    public function setInvoiceCustomer(InvoiceCustomer $invoiceCustomer): void {
	$this->invoiceCustomer = $invoiceCustomer;
    }

    public function setStreet(?string $street): void {
	$this->street = $street;
    }

    public function setCity(?string $city): void {
	$this->city = $city;
    }

    public function setZip(?string $zip): void {
	$this->zip = $zip;
    }

    public function setCountry(string $country): void {
	$this->country = $country;
    }

    public function setCountryISO(string $countryISO): void {
	$this->countryISO = $countryISO;
    }
    
    public function setLastUpdate(\DateTimeImmutable $lastUpdate): void {
	$this->lastUpdate = $lastUpdate;
    }

    public function setCreated(\DateTimeImmutable $created): void {
	$this->created = $created;
    }
}

