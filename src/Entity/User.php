<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
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
     * @ORM\OneToMany(targetEntity="App\Entity\Doc", mappedBy="user", orphanRemoval=true)
     */
    private $docs;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Classlist", mappedBy="user", orphanRemoval=true)
     */
    private $classlists;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Label", mappedBy="user", orphanRemoval=true)
     */
    private $labels;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Labelset", mappedBy="user", orphanRemoval=true)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Markup", mappedBy="user", orphanRemoval=true)
     */
    private $markups;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Markupset", mappedBy="user", orphanRemoval=true)
     */
    private $markupsets;

    public function __construct()
    {
        $this->docs = new ArrayCollection();
        $this->classlists = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->user = new ArrayCollection();
        $this->markups = new ArrayCollection();
        $this->markupsets = new ArrayCollection();
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
     * @see UserInterface
     */
    public function getPassword()
    {
        // not needed for apps that do not check user passwords
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed for apps that do not check user passwords
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
     * @return Collection|Label[]
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(Label $label): self
    {
        if (!$this->labels->contains($label)) {
            $this->labels[] = $label;
            $label->setUser($this);
        }

        return $this;
    }

    public function removeLabel(Label $label): self
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
            // set the owning side to null (unless already changed)
            if ($label->getUser() === $this) {
                $label->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Labelset[]
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(Labelset $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
            $user->setUser($this);
        }

        return $this;
    }

    public function removeUser(Labelset $user): self
    {
        if ($this->user->contains($user)) {
            $this->user->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getUser() === $this) {
                $user->setUser(null);
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
}
