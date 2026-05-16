<?php

namespace App\Entity;

use App\Repository\OrgansRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrgansRepository::class)]
class Organs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Denumirea organului este obligatorie.')]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $paried = null;

    #[ORM\Column(length: 20, options: ['default' => 'any'])]
    #[Assert\Choice(choices: ['male', 'female', 'any'], message: 'Alegeți aplicabilitatea corectă după sex.')]
    private string $gender_applicability = 'any';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_path = null;

    #[ORM\Column]
    private int $sort_order = 0;

    /**
     * @var Collection<int, OrganParameters>
     */
    #[ORM\OneToMany(mappedBy: 'organ', targetEntity: OrganParameters::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $organParameters;

    /**
     * @var Collection<int, UltrasoundTypeOrgan>
     */
    #[ORM\OneToMany(mappedBy: 'organ', targetEntity: UltrasoundTypeOrgan::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $ultrasoundTypeOrgans;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->organParameters = new ArrayCollection();
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

    public function isParied(): ?bool
    {
        return $this->paried;
    }

    public function setParied(bool $paried): static
    {
        $this->paried = $paried;

        return $this;
    }

    public function getGenderApplicability(): string
    {
        return $this->gender_applicability;
    }

    public function setGenderApplicability(string $gender_applicability): static
    {
        $this->gender_applicability = $gender_applicability;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(?string $image_path): static
    {
        $this->image_path = $image_path;

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
     * @return Collection<int, Parameters>
     */
    public function getParameters(): Collection
    {
        return new ArrayCollection(array_values(array_filter(
            $this->organParameters
                ->map(static fn (OrganParameters $organParameter): ?Parameters => $organParameter->getParameter())
                ->toArray()
        )));
    }

    public function addParameter(Parameters $parameter): static
    {
        foreach ($this->organParameters as $organParameter) {
            if ($organParameter->getParameter() === $parameter) {
                return $this;
            }
        }

        $organParameter = (new OrganParameters())
            ->setOrgan($this)
            ->setParameter($parameter)
        ;

        $this->organParameters->add($organParameter);

        return $this;
    }

    public function removeParameter(Parameters $parameter): static
    {
        foreach ($this->organParameters as $organParameter) {
            if ($organParameter->getParameter() === $parameter) {
                $this->removeOrganParameter($organParameter);
                break;
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrganParameters>
     */
    public function getOrganParameters(): Collection
    {
        return $this->organParameters;
    }

    public function addOrganParameter(OrganParameters $organParameter): static
    {
        if (!$this->organParameters->contains($organParameter)) {
            $this->organParameters->add($organParameter);
            $organParameter->setOrgan($this);
        }

        return $this;
    }

    public function removeOrganParameter(OrganParameters $organParameter): static
    {
        $this->organParameters->removeElement($organParameter);

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
            $ultrasoundTypeOrgan->setOrgan($this);
        }

        return $this;
    }

    public function removeUltrasoundTypeOrgan(UltrasoundTypeOrgan $ultrasoundTypeOrgan): static
    {
        $this->ultrasoundTypeOrgans->removeElement($ultrasoundTypeOrgan);

        return $this;
    }
}
