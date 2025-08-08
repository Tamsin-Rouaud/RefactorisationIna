<?php

namespace App\Tests\Repository;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\CustomWebTestCase;




class MediaRepositoryTest extends CustomWebTestCase
{

    public function testFindAllVisibleReturnsOnlyMediasFromUnblockedUsers(): void
{
    /** @var \App\Repository\MediaRepository $repo */
    $repo = $this->getDoctrine()->getRepository(Media::class);

    /** @var Media[] $result */
    $result = $repo->findAllVisible();

    $this->assertNotEmpty($result);

    foreach ($result as $media) {
        $user = $media->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->isBlocked(), 'L’utilisateur lié au média est bloqué.');
    }
}

public function testFindByUserQueryReturnsCorrectMedia(): void
    {
        // Récupère l'utilisateur Ina depuis une méthode utilitaire
        $ina = $this->getIna(); // méthode de CustomWebTestCase
        // $this->assertNotNull($ina, 'Utilisateur Ina non trouvé.');

        // Récupère le repository de Media
/** @var \App\Repository\MediaRepository $mediaRepo */
$mediaRepo = $this->getDoctrine()->getRepository(Media::class);


        // Lance la requête
        $query = $mediaRepo->findByUserQuery($ina);
        $results = $query->getResult();

        $this->assertIsArray($results);
        foreach ($results as $media) {
            $this->assertInstanceOf(Media::class, $media);
            $this->assertSame($ina->getId(), $media->getAlbum()?->getUser()?->getId(), 'Le média ne correspond pas à l’utilisateur attendu.');
        }
    }

}
