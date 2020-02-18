<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarkupsetRepository")
 */
class Markupset
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
     * @ORM\Column(type="integer")
     */
    private $level;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Markup", mappedBy="markupset", orphanRemoval=true)
     */
    private $markups;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="markupsets")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Course", mappedBy="markupsets")
     */
    private $courses;

    public function __construct()
    {
        $this->markups = new ArrayCollection();
        $this->courses = new ArrayCollection();
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

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Collection|Markup[]
     */
    public function getMarkups(): Collection
    {
        return $this->markups;
    }

    public function addMarkup(Markup $markup): self
    {
        if (!$this->markups->contains($markup)) {
            $this->markups[] = $markup;
            $markup->setMarkupset($this);
        }

        return $this;
    }

    public function removeMarkup(Markup $markup): self
    {
        if ($this->markups->contains($markup)) {
            $this->markups->removeElement($markup);
            // set the owning side to null (unless already changed)
            if ($markup->getMarkupset() === $this) {
                $markup->setMarkupset(null);
            }
        }

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
     * @return Collection|Course[]
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): self
    {
        if (!$this->courses->contains($course)) {
            $this->courses[] = $course;
            $course->addMarkupset($this);
        }

        return $this;
    }

    public function removeCourse(Course $course): self
    {
        if ($this->courses->contains($course)) {
            $this->courses->removeElement($course);
            $course->removeMarkupset($this);
        }

        return $this;
    }
}
