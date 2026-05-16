<?php

namespace App\Entity;

use App\Repository\ExaminationSessionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExaminationSessionsRepository::class)]
class ExaminationSessions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Patients $patient = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?UltrasoundType $ultrasound_type = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $session_date = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $session_note = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $session_conclusion = null;

    /**
     * @var Collection<int, ExaminationSessionOrgans>
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: ExaminationSessionOrgans::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['sort_order' => 'ASC'])]
    private Collection $session_organs;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->session_organs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patients
    {
        return $this->patient;
    }

    public function setPatient(?Patients $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getUltrasoundType(): ?UltrasoundType
    {
        return $this->ultrasound_type;
    }

    public function setUltrasoundType(?UltrasoundType $ultrasound_type): static
    {
        $this->ultrasound_type = $ultrasound_type;

        return $this;
    }

    public function getSessionDate(): ?\DateTimeImmutable
    {
        return $this->session_date;
    }

    public function setSessionDate(\DateTimeImmutable $session_date): static
    {
        $this->session_date = $session_date;

        return $this;
    }

    public function getSessionNote(): ?string
    {
        return $this->session_note;
    }

    public function setSessionNote(?string $session_note): static
    {
        $this->session_note = $session_note;

        return $this;
    }

    public function getSessionConclusion(): ?string
    {
        return $this->session_conclusion;
    }

    public function setSessionConclusion(?string $session_conclusion): static
    {
        $this->session_conclusion = $session_conclusion;

        return $this;
    }

    /**
     * @return Collection<int, ExaminationSessionOrgans>
     */
    public function getSessionOrgans(): Collection
    {
        return $this->session_organs;
    }

    public function addSessionOrgan(ExaminationSessionOrgans $session_organ): static
    {
        if (!$this->session_organs->contains($session_organ)) {
            $this->session_organs->add($session_organ);
            $session_organ->setSession($this);
        }

        return $this;
    }

    public function removeSessionOrgan(ExaminationSessionOrgans $session_organ): static
    {
        if ($this->session_organs->removeElement($session_organ) && $session_organ->getSession() === $this) {
            $session_organ->setSession(null);
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
