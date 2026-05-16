<?php

namespace App\Entity;

use App\Repository\ExaminationSessionOrgansRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExaminationSessionOrgansRepository::class)]
class ExaminationSessionOrgans
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'session_organs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ExaminationSessions $session = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Organs $organ = null;

    #[ORM\Column(length: 255)]
    private ?string $organ_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organ_image_path = null;

    #[ORM\Column(length: 20)]
    private ?string $side = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $organ_note = null;

    #[ORM\Column]
    private int $sort_order = 0;

    /**
     * @var Collection<int, ExaminationSessionParameterResults>
     */
    #[ORM\OneToMany(mappedBy: 'session_organ', targetEntity: ExaminationSessionParameterResults::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sort_order' => 'ASC'])]
    private Collection $parameter_results;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->parameter_results = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?ExaminationSessions
    {
        return $this->session;
    }

    public function setSession(?ExaminationSessions $session): static
    {
        $this->session = $session;

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

    public function getOrganName(): ?string
    {
        return $this->organ_name;
    }

    public function setOrganName(string $organ_name): static
    {
        $this->organ_name = $organ_name;

        return $this;
    }

    public function getOrganImagePath(): ?string
    {
        return $this->organ_image_path;
    }

    public function setOrganImagePath(?string $organ_image_path): static
    {
        $this->organ_image_path = $organ_image_path;

        return $this;
    }

    public function getSide(): ?string
    {
        return $this->side;
    }

    public function setSide(string $side): static
    {
        $this->side = $side;

        return $this;
    }

    public function getOrganNote(): ?string
    {
        return $this->organ_note;
    }

    public function setOrganNote(?string $organ_note): static
    {
        $this->organ_note = $organ_note;

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

    /**
     * @return Collection<int, ExaminationSessionParameterResults>
     */
    public function getParameterResults(): Collection
    {
        return $this->parameter_results;
    }

    public function addParameterResult(ExaminationSessionParameterResults $parameter_result): static
    {
        if (!$this->parameter_results->contains($parameter_result)) {
            $this->parameter_results->add($parameter_result);
            $parameter_result->setSessionOrgan($this);
        }

        return $this;
    }

    public function removeParameterResult(ExaminationSessionParameterResults $parameter_result): static
    {
        if ($this->parameter_results->removeElement($parameter_result) && $parameter_result->getSessionOrgan() === $this) {
            $parameter_result->setSessionOrgan(null);
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
