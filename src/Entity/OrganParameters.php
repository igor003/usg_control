<?php

namespace App\Entity;

use App\Repository\OrganParametersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganParametersRepository::class)]
#[ORM\Table(name: 'organ_parameters')]
class OrganParameters
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'organParameters')]
    #[ORM\JoinColumn(name: 'organ_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Organs $organ = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'parameter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Parameters $parameter = null;

    #[ORM\Column(name: 'sort_order')]
    private int $sortOrder = 0;

    public function getOrgan(): ?Organs
    {
        return $this->organ;
    }

    public function setOrgan(?Organs $organ): static
    {
        $this->organ = $organ;

        return $this;
    }

    public function getParameter(): ?Parameters
    {
        return $this->parameter;
    }

    public function setParameter(?Parameters $parameter): static
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
