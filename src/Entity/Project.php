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
     * @ORM\JoinColumn(nullable=true)
     */
    private $labelset;


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
     * @ORM\OneToMany(targetEntity=LtiAgs::class, mappedBy="project")
     */
    private $lti_grades;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="projects")
     */
    private $course;

    public function __construct()
    {
        $this->stages = new ArrayCollection();
        $this->markupsets = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
        $this->lti_grades = new ArrayCollection();
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

    /**
     * @return Collection|LtiAgs[]
     */
    public function getLtiGrades(): Collection
    {
        return $this->lti_grades;
    }

    public function addLtiGrade(LtiAgs $ltiGrade): self
    {
        if (!$this->lti_grades->contains($ltiGrade)) {
            $this->lti_grades[] = $ltiGrade;
            $ltiGrade->setProject($this);
        }

        return $this;
    }

    public function removeLtiGrade(LtiAgs $ltiGrade): self
    {
        if ($this->lti_grades->removeElement($ltiGrade)) {
            // set the owning side to null (unless already changed)
            if ($ltiGrade->getProject() === $this) {
                $ltiGrade->setProject(null);
            }
        }

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
}
