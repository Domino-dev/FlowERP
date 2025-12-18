<?php
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;

use App\Database\CustomerBillingAddressRepository;

#[ORM\Entity(repositoryClass: CustomerBillingAddressRepository::class)]
#[ORM\Table(name: "`customers_delivery_addresses`")]
class CustomerDeliveryAddress {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: "internal_id",type:"string", length:36, unique: true)]
    protected string $internalID;
    #[ORM\OneToOne(inversedBy: "customerDeliveryAddress",targetEntity:Customer::class)]
    #[ORM\JoinColumn(name: "customers_id", referencedColumnName: "id",nullable:false, onDelete: "CASCADE")]
    protected Customer $customer;
    #[ORM\Column(type: "string")]
    protected string $street;
    #[ORM\Column(type: "string")]
    protected string $city;
    #[ORM\Column(type: "string")]
    protected string $zip;
    #[ORM\Column(type: "string")]
    protected string $country;
    #[ORM\Column(name: 'country_iso',type: "string")]
    protected string $countryISO;
    #[ORM\Column(name:"last_update",type: "datetime", options: ["default" => "CURRENT_TIMESTAMP", "other" => "ON UPDATE CURRENT_TIMESTAMP"])]
    protected \DateTime $lastUpdate;
    #[ORM\Column(type: "datetime_immutable",options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(string $internalID, Customer $customer, string $street, string $city, string $zip,string $country, string $countryISO) {
	$this->internalID = $internalID;
	$this->customer = $customer;
	$this->street = $street;
	$this->city = $city;
	$this->zip = $zip;
	$this->country = $country;
	$this->countryISO = $countryISO;
	$this->lastUpdate = new \DateTime();
	$this->created = new \DateTimeImmutable();
    }
    
    public function getId(): int {
	return $this->id;
    }

    public function getInternalID(): string {
	return $this->internalID;
    }

    public function getCustomer(): Customer {
	return $this->customer;
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

    public function setCustomer(Customer $customer): void {
	$this->customer = $customer;
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