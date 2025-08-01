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
        $ina = $this->getReference('user_ina', User::class);
        $invite1 = $this->getReference('user_invite1', User::class);
        $invite2 = $this->getReference('user_invite2', User::class);

        // On récupère les 5 albums d’Ina
        $albums = [];
        for ($i = 1; $i <= 5; $i++) {
            $albums[] = $this->getReference("album_ina_$i", Album::class);
        }

        // 10 médias d’Ina, dispatchés dans ses albums
        for ($i = 1; $i <= 10; $i++) {
            $media = new Media();
            $media->setTitle("Photo Ina $i");
            $media->setPath("uploads/media/ina_$i.jpg");
            $media->setUser($ina);
            // Dispatch circulaire dans les 5 albums
            $media->setAlbum($albums[($i - 1) % 5]);
            $manager->persist($media);
        }

        // Médias d’invités sans album
        $media = new Media();
        $media->setTitle("Photo Invité Actif");
        $media->setPath("uploads/media/invite1.jpg");
        $media->setUser($invite1);
        $media->setAlbum(null);
        $manager->persist($media);

        $media = new Media();
        $media->setTitle("Photo Invité Bloqué");
        $media->setPath("uploads/media/invite2.jpg");
        $media->setUser($invite2);
        $media->setAlbum(null);
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
