<?php

namespace App\Entity;

use App\Repository\RatingsetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RatingsetRepository::class)
 */
class Ratingset
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=Rating::class, mappedBy="ratingset", cascade={"remove"})
     */
    private $rating;

    public function __construct()
    {
        $this->rating = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRating(): Collection
    {
        return $this->rating;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->rating->contains($rating)) {
            $this->rating[] = $rating;
            $rating->setRatingset($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->rating->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getRatingset() === $this) {
                $rating->setRatingset(null);
            }
        }

        return $this;
    }
}
