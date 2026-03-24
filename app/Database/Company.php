<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: "company")]

class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    #[ORM\Column(type: "string",length: 255)]
    protected string $name;
    #[ORM\Column(type: "string",nullable: true)]
    protected ?string $phone;
    #[ORM\Column(type: "string", length: 255)]
    protected ?string $email;
    #[ORM\Column(name: 'web_domain',type: "string", length: 255, unique: true)]
    protected string $webDomain;
    #[ORM\Column(name: "company_number",type: "string",length: 50, nullable:true)]
    protected ?string $companyNumber;
    #[ORM\Column(name: "vat_number",type: "string",length: 20, nullable:true)]
    protected ?string $vatNumber;
    #[ORM\Column(name: "bank_number",type: "string",length: 255, nullable:true)]
    protected ?string $bankNumber;
    #[ORM\Column(type: "string")]
    protected string $street;
    #[ORM\Column(type: "string")]
    protected string $city;
    #[ORM\Column(type: "string")]
    protected string $zip;
    #[ORM\Column(name: 'country_iso',type: "string")]
    protected string $countryISO;
    #[ORM\Column(name: "last_update",type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTime $lastUpdate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(
	    string $internalID, 
	    string $name, 
	    ?string $phone, 
	    ?string $email, 
	    string $webDomain, 
	    ?string $companyNumber, 
	    ?string $vatNumber, 
	    ?string $bankNumber, 
	    string $street,
	    string $city,
	    string $zip,
	    string $countryISO) {
	$this->internalID = $internalID;
	$this->name = $name;
	$this->phone = $phone;
	$this->email = $email;
	$this->webDomain = $webDomain;
	$this->companyNumber = $companyNumber;
	$this->vatNumber = $vatNumber;
	$this->bankNumber = $bankNumber;
	$this->street = $street;
	$this->city = $city;
	$this->zip = $zip;
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

    public function getName(): string {
	return $this->name;
    }

    public function getPhone(): ?string {
	return $this->phone;
    }

    public function getEmail(): ?string {
	return $this->email;
    }

    public function getWebDomain(): string {
	return $this->webDomain;
    }

    public function getCompanyNumber(): ?string {
	return $this->companyNumber;
    }

    public function getVatNumber(): ?string {
	return $this->vatNumber;
    }

    public function getBankNumber(): ?string {
	return $this->bankNumber;
    }

    public function getStreet(): string {
	return $this->street;
    }

    public function getCity(): string {
	return $this->city;
    }

    public function getZip(): string {
	return $this->zip;
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

    public function setName(string $name): void {
	$this->name = $name;
    }

    public function setPhone(?string $phone): void {
	$this->phone = $phone;
    }

    public function setEmail(?string $email): void {
	$this->email = $email;
    }

    public function setWebDomain(string $webDomain): void {
	$this->webDomain = $webDomain;
    }

    public function setCompanyNumber(?string $companyNumber): void {
	$this->companyNumber = $companyNumber;
    }

    public function setVatNumber(?string $vatNumber): void {
	$this->vatNumber = $vatNumber;
    }

    public function setBankNumber(?string $bankNumber): void {
	$this->bankNumber = $bankNumber;
    }

    public function setStreet(string $street): void {
	$this->street = $street;
    }

    public function setCity(string $city): void {
	$this->city = $city;
    }

    public function setZip(string $zip): void {
	$this->zip = $zip;
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
}