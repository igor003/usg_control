<?php

namespace App\Entity;

use App\Repository\PatientsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PatientsRepository::class)]
#[UniqueEntity(fields: ['idnp'], message: 'Există deja un pacient cu acest IDNP.', errorPath: 'idnp')]
class Patients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Prenumele este obligatoriu.')]
    #[Assert\Length(max: 100, maxMessage: 'Prenumele nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $first_name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Numele este obligatoriu.')]
    #[Assert\Length(max: 100, maxMessage: 'Numele nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $last_name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Sexul este obligatoriu.')]
    #[Assert\Choice(choices: ['female', 'male', 'other'], message: 'Alegeți un sex valid.')]
    private ?string $gender = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1900,
        max: 2026,
        notInRangeMessage: 'Anul nașterii trebuie să fie între {{ min }} și {{ max }}.'
    )]
    private ?int $birth_year = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\Length(max: 40, maxMessage: 'Telefonul nu poate avea mai mult de {{ limit }} caractere.')]
    #[Assert\Regex(pattern: '/^\+?[0-9\s().-]{6,40}$/', message: 'Introduceți un număr de telefon valid.')]
    private ?string $phone = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Cities $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Adresa nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Assert\Regex(pattern: '/^\d{13}$/', message: 'IDNP trebuie să conțină exact 13 cifre.')]
    private ?string $idnp = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Seria documentului nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $seria = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $beneficiary = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBirthYear(): ?int
    {
        return $this->birth_year;
    }

    public function setBirthYear(?int $birth_year): static
    {
        $this->birth_year = $birth_year;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCity(): ?Cities
    {
        return $this->city;
    }

    public function setCity(?Cities $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getIdnp(): ?string
    {
        return $this->idnp;
    }

    public function setIdnp(?string $idnp): static
    {
        $this->idnp = $idnp;

        return $this;
    }

    public function getSeria(): ?string
    {
        return $this->seria;
    }

    public function setSeria(?string $seria): static
    {
        $this->seria = $seria;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function isBeneficiary(): bool
    {
        return $this->beneficiary;
    }

    public function setBeneficiary(bool $beneficiary): static
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }
}
