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

    public function __construct()
    {
        $this->classlists = new ArrayCollection();
        $this->docs = new ArrayCollection();
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
}
