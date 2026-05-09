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
    #[Assert\NotBlank(message: 'Denumirea tipului UZI este obligatorie.')]
    #[Assert\Length(max: 255, maxMessage: 'Denumirea nu poate avea mai mult de {{ limit }} caractere.')]
    private ?string $name = null;

    #[ORM\Column]
    private int $sort_order = 0;

    /**
     * @var Collection<int, Organs>
     */
    #[ORM\OneToMany(mappedBy: 'ultrasound_type', targetEntity: Organs::class)]
    #[ORM\OrderBy(['sort_order' => 'ASC', 'name' => 'ASC', 'id' => 'ASC'])]
    private Collection $organs;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->organs = new ArrayCollection();
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

    /**
     * @return Collection<int, Organs>
     */
    public function getOrgans(): Collection
    {
        return $this->organs;
    }

    public function addOrgan(Organs $organ): static
    {
        if (!$this->organs->contains($organ)) {
            $this->organs->add($organ);
            $organ->setUltrasoundType($this);
        }

        return $this;
    }

    public function removeOrgan(Organs $organ): static
    {
        if ($this->organs->removeElement($organ) && $organ->getUltrasoundType() === $this) {
            $organ->setUltrasoundType(null);
        }

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
