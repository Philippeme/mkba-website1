<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Le code du projet est requis')]
    #[Assert\Regex(pattern: '/^[A-Z]{2,4}-[0-9]{4}$/', message: 'Format invalide (ex: PROJ-2024)')]
    private ?string $code = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La catégorie est requise')]
    #[Assert\Choice(choices: ['development', 'research', 'infrastructure', 'marketing', 'other'])]
    private ?string $category = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['low', 'medium', 'high', 'critical'])]
    private string $priority = 'medium';

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $budget = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $responsible = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'])]
    private string $status = 'planning';

    #[ORM\Column]
    private ?int $displayOrder = 0;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: ProjectTranslation::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $members;

    #[ORM\OneToMany(targetEntity: ProjectLink::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $links;

    #[ORM\OneToMany(targetEntity: ProjectAttachment::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->translations = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getBudget(): ?string
    {
        return $this->budget;
    }

    public function setBudget(?string $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getResponsible(): ?string
    {
        return $this->responsible;
    }

    public function setResponsible(?string $responsible): static
    {
        $this->responsible = $responsible;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, ProjectTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ProjectTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setProject($this);
        }

        return $this;
    }

    public function removeTranslation(ProjectTranslation $translation): static
    {
        if ($this->translations->removeElement($translation)) {
            if ($translation->getProject() === $this) {
                $translation->setProject(null);
            }
        }

        return $this;
    }

    public function getTranslation(string $locale): ?ProjectTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }
        return null;
    }

    public function getName(string $locale = 'fr'): ?string
    {
        $translation = $this->getTranslation($locale);
        return $translation ? $translation->getName() : null;
    }

    public function getDescription(string $locale = 'fr'): ?string
    {
        $translation = $this->getTranslation($locale);
        return $translation ? $translation->getDescription() : null;
    }

    /**
     * @return Collection<int, ProjectMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ProjectMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setProject($this);
        }

        return $this;
    }

    public function removeMember(ProjectMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getProject() === $this) {
                $member->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectLink>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function addLink(ProjectLink $link): static
    {
        if (!$this->links->contains($link)) {
            $this->links->add($link);
            $link->setProject($this);
        }

        return $this;
    }

    public function removeLink(ProjectLink $link): static
    {
        if ($this->links->removeElement($link)) {
            if ($link->getProject() === $this) {
                $link->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(ProjectAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setProject($this);
        }

        return $this;
    }

    public function removeAttachment(ProjectAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getProject() === $this) {
                $attachment->setProject(null);
            }
        }

        return $this;
    }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'development' => 'Développement',
            'research' => 'Recherche', 
            'infrastructure' => 'Infrastructure',
            'marketing' => 'Marketing',
            'other' => 'Autre',
            default => $this->category
        };
    }

    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Élevée',
            'critical' => 'Critique',
            default => $this->priority
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'planning' => 'Planification',
            'in_progress' => 'En cours',
            'on_hold' => 'En pause',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => $this->status
        };
    }

    public function __toString(): string
    {
        return $this->getName() ?: $this->code ?: 'Projet #' . $this->id;
    }
}