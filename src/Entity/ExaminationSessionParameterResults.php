<?php

namespace App\Entity;

use App\Repository\ExaminationSessionParameterResultsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExaminationSessionParameterResultsRepository::class)]
class ExaminationSessionParameterResults
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'parameter_results')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ExaminationSessionOrgans $session_organ = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Parameters $parameter = null;

    #[ORM\Column(length: 255)]
    private ?string $parameter_name = null;

    #[ORM\Column(length: 20)]
    private ?string $parameter_value_type = null;

    #[ORM\Column(nullable: true)]
    private ?array $parameter_value_content = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    #[ORM\Column]
    private int $sort_order = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionOrgan(): ?ExaminationSessionOrgans
    {
        return $this->session_organ;
    }

    public function setSessionOrgan(?ExaminationSessionOrgans $session_organ): static
    {
        $this->session_organ = $session_organ;

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

    public function getParameterName(): ?string
    {
        return $this->parameter_name;
    }

    public function setParameterName(string $parameter_name): static
    {
        $this->parameter_name = $parameter_name;

        return $this;
    }

    public function getParameterValueType(): ?string
    {
        return $this->parameter_value_type;
    }

    public function setParameterValueType(string $parameter_value_type): static
    {
        $this->parameter_value_type = $parameter_value_type;

        return $this;
    }

    public function getParameterValueContent(): ?array
    {
        return $this->parameter_value_content;
    }

    public function setParameterValueContent(?array $parameter_value_content): static
    {
        $this->parameter_value_content = $parameter_value_content;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function setSortOrder(int $sort_order): static
    {
        $this->sort_order = $sort_order;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
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
}
