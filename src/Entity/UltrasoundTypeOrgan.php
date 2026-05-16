<?php

namespace App\Entity;

use App\Repository\UltrasoundTypeOrganRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UltrasoundTypeOrganRepository::class)]
#[ORM\Table(name: 'ultrasound_type_organs')]
class UltrasoundTypeOrgan
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ultrasoundTypeOrgans')]
    #[ORM\JoinColumn(name: 'ultrasound_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?UltrasoundType $ultrasoundType = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ultrasoundTypeOrgans')]
    #[ORM\JoinColumn(name: 'organ_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Organs $organ = null;

    #[ORM\Column(name: 'sort_order')]
    private int $sortOrder = 0;

    public function getUltrasoundType(): ?UltrasoundType
    {
        return $this->ultrasoundType;
    }

    public function setUltrasoundType(?UltrasoundType $ultrasoundType): static
    {
        $this->ultrasoundType = $ultrasoundType;

        return $this;
    }

    public function getOrgan(): ?Organs
    {
        return $this->organ;
    }

    public function setOrgan(?Organs $organ): static
    {
        $this->organ = $organ;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = max(0, $sortOrder);

        return $this;
    }
}
