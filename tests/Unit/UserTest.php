<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Album;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class UserTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = new User();

        $user->setEmail('test@example.com');
        $user->setName('Test User');
        $user->setDescription('Description test');
        $user->setPassword('securepassword');
        $user->setIsBlocked(true);
        $user->setAdmin(true);

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('Test User', $user->getName());
        $this->assertSame('Description test', $user->getDescription());
        $this->assertSame('securepassword', $user->getPassword());
        $this->assertTrue($user->isBlocked());
        $this->assertTrue($user->isAdmin());
    }

    public function testUserIdentifierAndRoles(): void
    {
        $user = new User();
        $user->setName('admin');

        $this->assertSame('admin', $user->getUserIdentifier());
        $this->assertSame('admin', $user->getUsername());

        $user->setAdmin(false);
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $user->setAdmin(true);
        $this->assertEquals(['ROLE_ADMIN'], $user->getRoles());

        $this->assertNull($user->getSalt());
    }

    public function testAlbumsRelation(): void
    {
        $user = new User();
        $album = new Album();

        $this->assertCount(0, $user->getAlbums());

        $user->addAlbum($album);
        $this->assertCount(1, $user->getAlbums());
        $this->assertSame($user, $album->getUser());

        $user->removeAlbum($album);
        $this->assertCount(0, $user->getAlbums());
        $this->assertNull($album->getUser());
    }

    public function testDefaultCollectionsAreInitialized(): void
    {
        $user = new User();
        $this->assertInstanceOf(ArrayCollection::class, $user->getAlbums());
        $this->assertInstanceOf(ArrayCollection::class, $user->getMedias());
    }

    public function testSetMediasReplacesCollection(): void
{
    $user = new User();

    $media1 = $this->createMock(\App\Entity\Media::class);
    $media2 = $this->createMock(\App\Entity\Media::class);

    $collection = new \Doctrine\Common\Collections\ArrayCollection([$media1, $media2]);

    $user->setMedias($collection);

    $this->assertCount(2, $user->getMedias());
    $this->assertSame($collection, $user->getMedias());
}

}