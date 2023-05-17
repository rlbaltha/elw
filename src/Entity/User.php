<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastname;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Doc", mappedBy="user", orphanRemoval=true)
     */
    private $docs;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Classlist", mappedBy="user", orphanRemoval=true)
     */
    private $classlists;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Markup", mappedBy="user", orphanRemoval=true)
     */
    private $markups;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Markupset", mappedBy="user", orphanRemoval=true)
     */
    private $markupsets;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Project", mappedBy="user", orphanRemoval=true)
     */
    private $projects;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lti_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $d2l_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $theme = 'light';


    /**
     * @ORM\OneToMany(targetEntity=Rubric::class, mappedBy="user")
     */
    private $rubrics;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $irb;

    /**
     * @ORM\OneToMany(targetEntity=Rating::class, mappedBy="user", orphanRemoval=true)
     */
    private $ratings;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @ORM\OneToMany(targetEntity=Notification::class, mappedBy="from_user")
     */
    private $notifications;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $previouslogin;



    public function __construct()
    {
        $this->docs = new ArrayCollection();
        $this->classlists = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->markups = new ArrayCollection();
        $this->markupsets = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->rubricsets = new ArrayCollection();
        $this->rubrics = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->for_user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }



    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }


    /**
     * A visual identifier that represents this user.  Deprectated with 6.0
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return string the hashed password for this user
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

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
            $doc->setUser($this);
        }

        return $this;
    }

    public function removeDoc(Doc $doc): self
    {
        if ($this->docs->contains($doc)) {
            $this->docs->removeElement($doc);
            // set the owning side to null (unless already changed)
            if ($doc->getUser() === $this) {
                $doc->setUser(null);
            }
        }

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
            $classlist->setUser($this);
        }

        return $this;
    }

    public function removeClasslist(Classlist $classlist): self
    {
        if ($this->classlists->contains($classlist)) {
            $this->classlists->removeElement($classlist);
            // set the owning side to null (unless already changed)
            if ($classlist->getUser() === $this) {
                $classlist->setUser(null);
            }
        }

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
            $markup->setUser($this);
        }

        return $this;
    }

    public function removeMarkup(Markup $markup): self
    {
        if ($this->markups->contains($markup)) {
            $this->markups->removeElement($markup);
            // set the owning side to null (unless already changed)
            if ($markup->getUser() === $this) {
                $markup->setUser(null);
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
            $markupset->setUser($this);
        }

        return $this;
    }

    public function removeMarkupset(Markupset $markupset): self
    {
        if ($this->markupsets->contains($markupset)) {
            $this->markupsets->removeElement($markupset);
            // set the owning side to null (unless already changed)
            if ($markupset->getUser() === $this) {
                $markupset->setUser(null);
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
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        if ($this->projects->contains($project)) {
            $this->projects->removeElement($project);
            // set the owning side to null (unless already changed)
            if ($project->getUser() === $this) {
                $project->setUser(null);
            }
        }

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function isEqualTo(UserInterface $user): ?bool
    {
        return true;
    }

    public function getLtiId(): ?int
    {
        return $this->lti_id;
    }

    public function setLtiId(?int $lti_id): self
    {
        $this->lti_id = $lti_id;

        return $this;
    }

    public function getD2lId(): ?string
    {
        return $this->d2l_id;
    }

    public function setD2lId(?string $d2l_id): self
    {
        $this->d2l_id = $d2l_id;

        return $this;
    }


    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;

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
            $rubric->setUser($this);
        }

        return $this;
    }

    public function removeRubric(Rubric $rubric): self
    {
        if ($this->rubrics->removeElement($rubric)) {
            // set the owning side to null (unless already changed)
            if ($rubric->getUser() === $this) {
                $rubric->setUser(null);
            }
        }

        return $this;
    }

    public function getIrb(): ?int
    {
        return $this->irb;
    }

    public function setIrb(?int $irb): self
    {
        $this->irb = $irb;

        return $this;
    }

    /**
     * @return Collection|Rating[]
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings[] = $rating;
            $rating->setUser($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getUser() === $this) {
                $rating->setUser(null);
            }
        }

        return $this;
    }

    public function getLastlogin(): ?\DateTimeInterface
    {
        return $this->lastlogin;
    }

    public function setLastlogin(?\DateTimeInterface $lastlogin): self
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setFromUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getFromUser() === $this) {
                $notification->setFromUser(null);
            }
        }

        return $this;
    }

    public function getPreviouslogin(): ?\DateTimeInterface
    {
        return $this->previouslogin;
    }

    public function setPreviouslogin(?\DateTimeInterface $previouslogin): self
    {
        $this->previouslogin = $previouslogin;

        return $this;
    }


}