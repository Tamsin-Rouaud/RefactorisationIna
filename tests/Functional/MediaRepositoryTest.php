<?php

namespace App\Tests\Repository;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\CustomWebTestCase;

class MediaRepositoryTest extends CustomWebTestCase
{
    public function testFindAllVisibleReturnsOnlyMediasFromUnblockedUsers(): void
    {
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
            \App\DataFixtures\MediaFixtures::class,
        ], static::getContainer());

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');

        /** @var \App\Repository\MediaRepository $repo */
        $repo = $doctrine->getRepository(Media::class);

        /** @var Media[] $result */
        $result = $repo->findAllVisible();

        $this->assertNotEmpty($result);

        foreach ($result as $media) {
            $user = $media->getUser();
            $this->assertInstanceOf(User::class, $user);
            $this->assertFalse($user->isBlocked(), 'L’utilisateur lié au média est bloqué.');
        }
    }
}
