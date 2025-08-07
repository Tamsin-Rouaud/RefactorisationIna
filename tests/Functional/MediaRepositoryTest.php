<?php

namespace App\Tests\Repository;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\CustomWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;



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

}
