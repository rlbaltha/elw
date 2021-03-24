<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 */
class Project
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $color;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="projects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Labelset", inversedBy="projects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $labelset;

    /**
     * @ORM\ManyToOne(targetEntity=Rubricset::class, inversedBy="projects")
     */
    private $rubricset;

    /**
     * @ORM\ManyToMany(targetEntity=Stage::class, inversedBy="projects")
     */
    private $stages;

    /**
     * @ORM\ManyToMany(targetEntity=Markupset::class, inversedBy="projects")
     */
    private $markupsets;

    public function __construct()
    {
        $this->stages = new ArrayCollection();
        $this->markupsets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLabelset(): ?Labelset
    {
        return $this->labelset;
    }

    public function setLabelset(?Labelset $labelset): self
    {
        $this->labelset = $labelset;

        return $this;
    }

    public function getRubricset(): ?Rubricset
    {
        return $this->rubricset;
    }

    public function setRubricset(?Rubricset $rubricset): self
    {
        $this->rubricset = $rubricset;

        return $this;
    }

    /**
     * @return Collection|Stage[]
     */
    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function addStage(Stage $stage): self
    {
        if (!$this->stages->contains($stage)) {
            $this->stages[] = $stage;
        }

        return $this;
    }

    public function removeStage(Stage $stage): self
    {
        $this->stages->removeElement($stage);

        return $this;
    }

    /**
     * @return Collection|Markupset[]
     */
    public function getMarkupsets(): Collection
    {
        return $this->markupsets;
    }

    public function addMarkupset(Markupset $markupset): self
    {
        if (!$this->markupsets->contains($markupset)) {
            $this->markupsets[] = $markupset;
        }

        return $this;
    }

    public function removeMarkupset(Markupset $markupset): self
    {
        $this->markupsets->removeElement($markupset);

        return $this;
    }
}
