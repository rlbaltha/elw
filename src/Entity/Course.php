<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CourseRepository")
 */
class Course
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
     * @ORM\OneToMany(targetEntity="App\Entity\Classlist", mappedBy="course", orphanRemoval=true)
     */
    private $classlists;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Doc", mappedBy="course", orphanRemoval=true)
     */
    private $docs;


    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Markupset", inversedBy="courses")
     */
    private $markupsets;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $announcement = "Welcome to our class.";

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Term")
     */
    private $term;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lti_id;

    /**
     * @ORM\OneToMany(targetEntity=LtiAgs::class, mappedBy="course", orphanRemoval=true)
     */
    private $ltiAgs;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="course")
     */
    private $projects;



    public function __construct()
    {
        $this->classlists = new ArrayCollection();
        $this->docs = new ArrayCollection();
        $this->markupsets = new ArrayCollection();
        $this->ltiAgs = new ArrayCollection();
        $this->projects = new ArrayCollection();
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

    /**
     * @return Collection|Classlist[]
     */
    public function getClasslists(): Collection
    {
        return $this->classlists;
    }

    public function addClasslist(Classlist $classlist): self
    {
        if (!$this->classlists->contains($classlist)) {
            $this->classlists[] = $classlist;
            $classlist->setCourse($this);
        }

        return $this;
    }

    public function removeClasslist(Classlist $classlist): self
    {
        if ($this->classlists->contains($classlist)) {
            $this->classlists->removeElement($classlist);
            // set the owning side to null (unless already changed)
            if ($classlist->getCourse() === $this) {
                $classlist->setCourse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Doc[]
     */
    public function getDocs(): Collection
    {
        return $this->docs;
    }

    public function addDoc(Doc $doc): self
    {
        if (!$this->docs->contains($doc)) {
            $this->docs[] = $doc;
            $doc->setCourse($this);
        }

        return $this;
    }

    public function removeDoc(Doc $doc): self
    {
        if ($this->docs->contains($doc)) {
            $this->docs->removeElement($doc);
            // set the owning side to null (unless already changed)
            if ($doc->getCourse() === $this) {
                $doc->setCourse(null);
            }
        }

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
        if ($this->markupsets->contains($markupset)) {
            $this->markupsets->removeElement($markupset);
        }

        return $this;
    }

    public function getAnnouncement(): ?string
    {
        return $this->announcement;
    }

    public function setAnnouncement(?string $announcement): self
    {
        $this->announcement = $announcement;

        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(?string $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getTerm(): ?Term
    {
        return $this->term;
    }

    public function setTerm(?Term $term): self
    {
        $this->term = $term;

        return $this;
    }

    public function getLtiId(): ?string
    {
        return $this->lti_id;
    }

    public function setLtiId(?string $lti_id): self
    {
        $this->lti_id = $lti_id;

        return $this;
    }

    /**
     * @return Collection|LtiAgs[]
     */
    public function getLtiAgs(): Collection
    {
        return $this->ltiAgs;
    }

    public function addLtiAg(LtiAgs $ltiAg): self
    {
        if (!$this->ltiAgs->contains($ltiAg)) {
            $this->ltiAgs[] = $ltiAg;
            $ltiAg->setCourse($this);
        }

        return $this;
    }

    public function removeLtiAg(LtiAgs $ltiAg): self
    {
        if ($this->ltiAgs->removeElement($ltiAg)) {
            // set the owning side to null (unless already changed)
            if ($ltiAg->getCourse() === $this) {
                $ltiAg->setCourse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Project[]
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setCourse($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getCourse() === $this) {
                $project->setCourse(null);
            }
        }

        return $this;
    }


}
