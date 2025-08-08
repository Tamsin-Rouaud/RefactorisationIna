<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AlbumFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Album;
use App\Tests\Functional\CustomWebTestCase;


class AlbumFixturesTest extends CustomWebTestCase
{
    public function testGetDependenciesReturnsCorrectClass(): void
    {
        $fixtures = new AlbumFixtures();

        $expected = [
            UserFixtures::class,
        ];

        $this->assertSame($expected, $fixtures->getDependencies());
    }

     public function testBlockedAlbumIsLoadedIfUserExists(): void
{
    $album = $this->getDoctrine()
        ->getRepository(Album::class)
        ->findOneBy(['name' => 'Album Bloqué']);

    $this->assertNotNull($album, 'L’album "Album Bloqué" n’a pas été trouvé dans la base de test.');

    
    $this->assertNotNull($album->getUser(), 'Aucun utilisateur associé à l’album.');
    $this->assertTrue($album->getUser()->isBlocked(), 'Le propriétaire de l’album devrait être bloqué.');
}

}
