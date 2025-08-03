<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Album;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AlbumFixtures extends Fixture implements DependentFixtureInterface
{
    
    // Albums pour Ina
    public function load(ObjectManager $manager): void
    {
        // 5 albums pour Ina
        for ($i = 1; $i <= 5; $i++) {
            $album = new Album();
            $album->setName("Album Ina $i");
            $album->setUser($this->getReference('user_ina', User::class));

            $manager->persist($album);
            $this->addReference("album_ina_$i", $album);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
