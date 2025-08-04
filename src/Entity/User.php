<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;


    #[ORM\Column]
    private bool $admin = false;

    #[ORM\Column(unique: true, nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(length: 180, unique: true, nullable: false)]
    #[Assert\NotBlank(message: 'L’email est obligatoire.')]
    #[Assert\Email(message: 'L’email est invalide.')]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isBlocked = false;

    /** @var Collection<int, Media> */
    #[ORM\OneToMany(targetEntity: Media::class, cascade: ['remove'], mappedBy: 'user')]
    private Collection $medias;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'user', cascade:['remove'])]
    private Collection $albums;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->albums = new ArrayCollection();
    }

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getEmail(): ?string 
    {
        return $this->email; 
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string 
    {
        return $this->name; 
    }

    public function setName(?string $name): void 
    {
        $this->name = $name; 
    }

    public function getDescription(): ?string 
    { 
        return $this->description; 
    }

    public function setDescription(?string $description): void 
    { 
        $this->description = $description; 
    }

    /**
    * @return Collection<int, Media>
    */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    /**
    * @param Collection<int, Media> $medias
    */
    public function setMedias(Collection $medias): void
    {
        $this->medias = $medias;
    }


    public function isAdmin(): bool 
    {
        return $this->admin; 
    }

    public function setAdmin(bool $admin): void 
    {
        $this->admin = $admin; 
    }

    // === Méthodes exigées par Symfony Security ===

    public function getUserIdentifier(): string
    {
        return !empty($this->name) ? $this->name : 'utilisateur';
    }

    public function getPassword(): string
    {
        return $this->password ?? '';
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->admin ? ['ROLE_ADMIN'] : ['ROLE_USER'];
    }

    public function eraseCredentials(): void {}

    // Pour éviter les erreurs d'Intelephense dans VS Code :
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setUser($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
           
            if ($album->getUser() === $this) {
                $album->setUser(null);
            }
        }

        return $this;
    }

}
