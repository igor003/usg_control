<?php

namespace App\Entity;

use App\Repository\CitiesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CitiesRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CITIES_DISTRICT_NAME', columns: ['district_id', 'name'])]
class Cities
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'cities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Districts $district = null;

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDistrict(): ?Districts
    {
        return $this->district;
    }

    public function setDistrict(?Districts $district): static
    {
        $this->district = $district;

        return $this;
    }
}
