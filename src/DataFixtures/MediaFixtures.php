<?php

namespace App\DataFixtures;

use App\Entity\Media;
use App\Entity\User;
use App\Entity\Album;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class MediaFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Médias pour Album Ina 1
        for ($i = 1; $i <= 2; $i++) {
            $media = new Media();
            $media->setTitle("Photo Ina $i");
            $media->setPath("uploads/media/000$i.jpg");
            $media->setAlbum($this->getReference('album_ina_1', Album::class));
            $media->setUser($this->getReference('user_ina', User::class));
            $manager->persist($media);
        }

        // Média pour Album Ina 2
        $media = new Media();
        $media->setTitle("Photo Ina 3");
        $media->setPath("uploads/media/0003.jpg");
        $media->setAlbum($this->getReference('album_ina_2', Album::class));
        $media->setUser($this->getReference('user_ina', User::class));
        $manager->persist($media);

        // Média pour Invité actif
        $media = new Media();
        $media->setTitle("Photo Invité Actif");
        $media->setPath("uploads/media/0004.jpg");
        $media->setAlbum($this->getReference('album_invite1', Album::class));
        $media->setUser($this->getReference('user_invite1', User::class));
        $manager->persist($media);

        // Média pour Invité bloqué
        $media = new Media();
        $media->setTitle("Photo Invité Bloqué");
        $media->setPath("uploads/media/invite2.jpg");
        $media->setAlbum($this->getReference('album_invite2', Album::class));
        $media->setUser($this->getReference('user_invite2', User::class));
        $manager->persist($media);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            AlbumFixtures::class,
        ];
    }
}
