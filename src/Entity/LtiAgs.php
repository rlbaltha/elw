<?php

namespace App\Entity;

use App\Repository\LtiAgsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LtiAgsRepository::class)
 */
class LtiAgs
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\Column(type="string", length=1020)
     */
    private $lti_id;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="ltiAgs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $course;

    /**
     * @ORM\Column(type="integer")
     */
    private $max;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="lti_grades")
     */
    private $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLtiId(): ?string
    {
        return $this->lti_id;
    }

    public function setLtiId(string $lti_id): self
    {
        $this->lti_id = $lti_id;

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

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function setMax(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}
