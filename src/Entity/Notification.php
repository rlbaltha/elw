<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $action;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $docid;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $reviewid;


    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications")
     */
    private $from_user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $for_user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $courseid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getDocid(): ?int
    {
        return $this->docid;
    }

    public function setDocid(?int $docid): self
    {
        $this->docid = $docid;

        return $this;
    }

    public function getReviewid(): ?int
    {
        return $this->reviewid;
    }

    public function setReviewid(?int $reviewid): self
    {
        $this->reviewid = $reviewid;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getFromUser(): ?User
    {
        return $this->from_user;
    }

    public function setFromUser(?User $from_user): self
    {
        $this->from_user = $from_user;

        return $this;
    }

    public function getForUser(): ?int
    {
        return $this->for_user;
    }

    public function setForUser(?int $for_user): self
    {
        $this->for_user = $for_user;

        return $this;
    }

    public function getCourseid(): ?int
    {
        return $this->courseid;
    }

    public function setCourseid(?int $courseid): self
    {
        $this->courseid = $courseid;

        return $this;
    }

}
