<?php

namespace App\Entity;

use App\Repository\UltrasoundTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UltrasoundTypeRepository::class)]
#[ORM\Table(name: 'ultrasound_types')]
class UltrasoundType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Denumirea tipului USG este obligatorie.')]
    #[Assert\Length(max: 255, maxMessage: 'Denumirea nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $name = null;

    #[ORM\Column]
    private int $sort_order = 0;

    /**
     * @var Collection<int, UltrasoundTypeOrgan>
     */
    #[ORM\OneToMany(mappedBy: 'ultrasoundType', targetEntity: UltrasoundTypeOrgan::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $ultrasoundTypeOrgans;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->ultrasoundTypeOrgans = new ArrayCollection();
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

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function setSortOrder(int $sort_order): static
    {
        $this->sort_order = max(0, $sort_order);

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

    /**
     * @return Collection<int, UltrasoundTypeOrgan>
     */
    public function getUltrasoundTypeOrgans(): Collection
    {
        return $this->ultrasoundTypeOrgans;
    }

    public function addUltrasoundTypeOrgan(UltrasoundTypeOrgan $ultrasoundTypeOrgan): static
    {
        if (!$this->ultrasoundTypeOrgans->contains($ultrasoundTypeOrgan)) {
            $this->ultrasoundTypeOrgans->add($ultrasoundTypeOrgan);
            $ultrasoundTypeOrgan->setUltrasoundType($this);
        }

        return $this;
    }

    public function removeUltrasoundTypeOrgan(UltrasoundTypeOrgan $ultrasoundTypeOrgan): static
    {
        $this->ultrasoundTypeOrgans->removeElement($ultrasoundTypeOrgan);

        return $this;
    }
}
