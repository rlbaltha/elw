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
     * @ORM\ManyToMany(targetEntity="App\Entity\Labelset")
     */
    private $labelsets;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Markupset", inversedBy="courses")
     */
    private $markupsets;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $announcement = "Welcome to our class.";


    public function __construct()
    {
        $this->classlists = new ArrayCollection();
        $this->docs = new ArrayCollection();
        $this->labelsets = new ArrayCollection();
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
     * @return Collection|Labelset[]
     */
    public function getLabelsets(): Collection
    {
        return $this->labelsets;
    }

    public function addLabelset(Labelset $labelset): self
    {
        if (!$this->labelsets->contains($labelset)) {
            $this->labelsets[] = $labelset;
        }

        return $this;
    }

    public function removeLabelset(Labelset $labelset): self
    {
        if ($this->labelsets->contains($labelset)) {
            $this->labelsets->removeElement($labelset);
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

}
