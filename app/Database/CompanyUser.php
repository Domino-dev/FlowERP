<?php 
declare(strict_types=1);
namespace App\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use App\Database\Invoice;

#[ORM\Entity(repositoryClass: CompanyUserRepository::class)]
#[ORM\Table(name: "company_users")]

class CompanyUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;
    #[ORM\Column(name: 'internal_id', type: "string", length: 36, options: ["fixed" => true ], unique: true)]
    protected string $internalID;
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: "companyUser")]
    private ?Collection $invoice = null;
    #[ORM\Column(type: "string",length: 255)]
    protected string $name;
    #[ORM\Column(type: "text",nullable: true)]
    protected ?string $note;
    #[ORM\Column(type: "string", length: 15)]
    protected ?string $phone;
    #[ORM\Column(type: "string", length: 255, unique: true)]
    protected string $email;
    #[ORM\Column(type: "string", length: 128)]
    protected string $password;
    #[ORM\Column(type: "string",length: 64)]
    protected string $role;    
    #[ORM\Column(name:"is_main",type: "boolean", options: ["default" => true])]
    protected bool $isMain;
    #[ORM\Column(name:"is_enabled",type: "boolean", options: ["default" => true])]
    protected bool $isEnabled;
    #[ORM\Column(name: "last_update",type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTime $lastUpdate;
    #[ORM\Column(type: "datetime_immutable", options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeImmutable $created;
    
    public function __construct(string $internalID, string $name, ?string $note, ?string $phone, string $email, string $password, string $role, bool $isMain = false, bool $isEnabled = true) {
	$this->internalID = $internalID;
	$this->name = $name;
	$this->note = $note;
	$this->phone = $phone;
	$this->email = $email;
	$this->password = $password;
	$this->role = $role;
	$this->isMain = $isMain;
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

    public function getInvoice(): ?Collection {
	return $this->invoice;
    }
    
    public function getName(): string {
	return $this->name;
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

    public function getPassword(): string {
	return $this->password;
    }

    public function getRole(): string {
	return $this->role;
    }

    public function getIsMain(): bool {
	return $this->isMain;
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

    public function setInvoice(?Collection $invoice): void {
	$this->invoice = $invoice;
    }
    
    public function setName(string $name): void {
	$this->name = $name;
    }

    public function setNote(?string $note): void {
	$this->note = $note;
    }

    public function setPhone(?string $phone): void {
	$this->phone = $phone;
    }

    public function setEmail(string $email): void {
	$this->email = $email;
    }

    public function setPassword(string $password): void {
	$this->password = $password;
    }

    public function setRole(string $role): void {
	$this->role = $role;
    }

    public function setIsMain(bool $isMain): void {
	$this->isMain = $isMain;
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
}