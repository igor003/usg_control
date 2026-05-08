<?php

namespace App\Entity;

use App\Repository\ParametersRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParametersRepository::class)]
class Parameters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Denumirea este obligatorie.')]
    #[Assert\Length(max: 255, maxMessage: 'Denumirea nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Tipul valorii este obligatoriu.')]
    #[Assert\Choice(choices: ['text', 'select'], message: 'Alegeți un tip de valoare valid.')]
    private ?string $value_type = null;

    #[ORM\Column]
    private array $value_content = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

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

    public function getValueType(): ?string
    {
        return $this->value_type;
    }

    public function setValueType(string $value_type): static
    {
        $this->value_type = $value_type;

        return $this;
    }

    public function getValueContent(): array
    {
        return $this->value_content;
    }

    public function setValueContent(array $value_content): static
    {
        $this->value_content = $value_content;

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
