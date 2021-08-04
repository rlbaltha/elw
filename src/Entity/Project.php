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
     * @ORM\ManyToMany(targetEntity=Stage::class, inversedBy="projects")
     */
    private $stages;

    /**
     * @ORM\ManyToMany(targetEntity=Markupset::class, inversedBy="projects")
     */
    private $markupsets;

    /**
     * @ORM\ManyToMany(targetEntity=Rubric::class, inversedBy="projects")
     */
    private $rubrics;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="projects")
     */
    private $course;

    /**
     * @ORM\ManyToMany(targetEntity=LtiAgs::class, inversedBy="projects")
     */
    private $ltigrades;

    public function __construct()
    {
        $this->stages = new ArrayCollection();
        $this->markupsets = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
        $this->ltigrades = new ArrayCollection();
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

    /**
     * @return Collection|Rubric[]
     */
    public function getRubrics(): Collection
    {
        return $this->rubrics;
    }

    public function addRubric(Rubric $rubric): self
    {
        if (!$this->rubrics->contains($rubric)) {
            $this->rubrics[] = $rubric;
        }

        return $this;
    }

    public function removeRubric(Rubric $rubric): self
    {
        $this->rubrics->removeElement($rubric);

        return $this;
    }


    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return Collection|LtiAgs[]
     */
    public function getLtigrades(): Collection
    {
        return $this->ltigrades;
    }

    public function addLtigrade(LtiAgs $ltigrade): self
    {
        if (!$this->ltigrades->contains($ltigrade)) {
            $this->ltigrades[] = $ltigrade;
        }

        return $this;
    }

    public function removeLtigrade(LtiAgs $ltigrade): self
    {
        $this->ltigrades->removeElement($ltigrade);

        return $this;
    }

}
