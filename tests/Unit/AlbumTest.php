<?php

namespace App\Tests\Entity;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;


class AlbumTest extends TestCase
{
    public function testInitialState(): void
    {
        $album = new Album();

        $this->assertNull($album->getId());
        $this->assertInstanceOf(ArrayCollection::class, $album->getMedias());
        $this->assertCount(0, $album->getMedias());
    }

    public function testNameGetterAndSetter(): void
    {
        $album = new Album();
        $album->setName('Voyage');

        $this->assertSame('Voyage', $album->getName());
    }

    public function testUserGetterAndSetter(): void
    {
        $album = new Album();
        $user = new User();

        $album->setUser($user);

        $this->assertSame($user, $album->getUser());
    }
    public function testAddAndRemoveMedia(): void
    {
        $album = new Album();
        $media = new \App\Entity\Media();

        // Avant ajout
        $this->assertCount(0, $album->getMedias());
        $this->assertNull($media->getAlbum());

        // Ajout
        $album->addMedia($media);
        $this->assertCount(1, $album->getMedias());
        $this->assertSame($album, $media->getAlbum());

        // Suppression
        $album->removeMedia($media);
        $this->assertCount(0, $album->getMedias());
        $this->assertNull($media->getAlbum());
    }

    public function testSetMedias(): void
    {
        $media1 = new Media();
        $media2 = new Media();

        $collection = new ArrayCollection([$media1, $media2]);

        $album = new Album();
        $album->setMedias($collection);

        $this->assertSame($collection, $album->getMedias());
    }

}