<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column]
    private string $name;

    /** @var Collection<int, Media> */
    #[ORM\OneToMany(mappedBy: "album", targetEntity: Media::class, cascade: ['remove'])]
    private Collection $medias;

    public function __construct()
{
    $this->medias = new ArrayCollection();
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

    #[ORM\ManyToOne(inversedBy: 'albums')]
    private ?User $user = null;


/** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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

    public function addMedia(Media $media): static
{
    if (!$this->medias->contains($media)) {
        $this->medias->add($media);
        $media->setAlbum($this);
    }

    return $this;
}

public function removeMedia(Media $media): static
{
    if ($this->medias->removeElement($media)) {
        // set the owning side to null (unless already changed)
        if ($media->getAlbum() === $this) {
            $media->setAlbum(null);
        }
    }

    return $this;
}

}
