<?php
namespace App\DataFixtures;

use App\Entity\Media;
use App\Entity\Album;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class MediaFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Liste des images disponibles 
        $availableImages = [
            '0002.jpg', '0003.jpg', '0004.jpg', '0005.jpg', '0006.jpg',
            '0007.jpg', '0008.jpg', '0009.jpg', '0010.jpg', '0011.jpg',
        ];

        // Total d’albums : 5 pour Ina + 2 x 6 pour les invités = 17
        $totalAlbumsIna = 5;
        $totalInvites = 6;
        $albums = [];

        // Albums d’Ina
        for ($i = 1; $i <= $totalAlbumsIna; $i++) {
            $albums[] = $this->getReference("album_ina_$i", Album::class);
        }

        // Albums des invités
        for ($invite = 1; $invite <= $totalInvites; $invite++) {
            for ($j = 1; $j <= 2; $j++) {
                $albums[] = $this->getReference("album_invite{$invite}_{$j}", Album::class);
            }
        }

        $imageCount = count($availableImages);
        $imageIndex = 0;
        $mediaCounter = 1;

        foreach ($albums as $album) {
            for ($i = 0; $i < 5; $i++) {
                $media = new Media();
                $media->setTitle("Media $mediaCounter - " . $album->getName());

                // Utilisation circulaire des images disponibles
                $imageFilename = $availableImages[$imageIndex % $imageCount];
                $media->setPath("uploads/$imageFilename");

                $media->setAlbum($album);
                $media->setUser($album->getUser());

                $manager->persist($media);

                $imageIndex++;
                $mediaCounter++;
            }
        }

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
