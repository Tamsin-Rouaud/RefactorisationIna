<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Album;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AlbumFixtures extends Fixture implements DependentFixtureInterface
{

   


    public function load(ObjectManager $manager): void
    {
        // Albums pour Ina
        for ($i = 1; $i <= 2; $i++) {
            $album = new Album();
            $album->setName("Album Ina $i");
            $album->setUser($this->getReference('user_ina', User::class));
            $manager->persist($album);
            $this->addReference("album_ina_$i", $album);
        }

        // Album pour invité actif
        $album1 = new Album();
        $album1->setName("Album Invité Actif");
        $album1->setUser($this->getReference('user_invite1', User::class));
        $manager->persist($album1);
        $this->addReference("album_invite1", $album1);

        // Album pour invité bloqué
        $album2 = new Album();
        $album2->setName("Album Invité Bloqué");
        $album2->setUser($this->getReference('user_invite2', User::class));
        $manager->persist($album2);
        $this->addReference("album_invite2", $album2);


        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
