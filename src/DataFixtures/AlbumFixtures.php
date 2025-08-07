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
        // 5 albums pour Ina
        for ($i = 1; $i <= 5; $i++) {
            $album = new Album();
            $album->setName("Album Ina $i");
            $album->setUser($this->getReference('user_ina', User::class));

            $manager->persist($album);
            $this->addReference("album_ina_$i", $album);
        }

        // 2 albums pour chaque invité (invite1 à invite6)
        for ($i = 1; $i <= 6; $i++) {
            $userRef = "user_invite$i";
            for ($j = 1; $j <= 2; $j++) {
                $album = new Album();
                $album->setName("Album Invite{$i}_{$j}");
                $album->setUser($this->getReference($userRef, User::class));

                $manager->persist($album);
                $this->addReference("album_invite{$i}_{$j}", $album);
            }
        }

        // Album pour l'utilisateur bloqué (si présent)
        try {
            $blockedUser = $this->getReference('user_blocked', User::class);

            $album = new Album();
            $album->setName('Album Bloqué');
            $album->setUser($blockedUser);

            $manager->persist($album);
            $this->addReference('album_blocked', $album);
        } catch (\OutOfBoundsException $e) {
            // La référence n'existe pas, on ne fait rien
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
