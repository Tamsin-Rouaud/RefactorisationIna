<?php

namespace App\Tests\Controller;


use App\Entity\Album;
use App\Entity\User;
use App\Tests\Functional\CustomWebTestCase;


class HomeControllerTest extends CustomWebTestCase
{

    public function testHomePageIsAccessible(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testAboutPageIsAccessible(): void
    {
        $this->client->request('GET', '/about');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }


    public function testGuestsListShowsOnlyUnblockedUsers(): void
    {
        $crawler = $this->client->request('GET', '/guests');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Jean Dupont');
        $this->assertSelectorTextNotContains('body', 'Marie Durand'); 
    }


   public function testGuestPageAccessibleIfNotBlocked(): void
    {
        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $invite1 = $userRepo->findOneBy(['name' => 'Jean Dupont']);

        $this->assertNotNull($invite1);
        $this->client->request('GET', '/guest/' . $invite1->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Jean Dupont');
    }


    public function testGuestPageBlockedForNonAdmin(): void
    {
        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $blocked = $userRepo->findOneBy(['name' => 'Marie Durand']);
        $this->assertNotNull($blocked, 'Utilisateur bloqué introuvable.');

        $this->client->request('GET', '/guest/' . $blocked->getId());

        $this->assertResponseStatusCodeSame(404);
    }


    public function testGuestPageAccessibleForAdmin(): void
    {
        // Connexion en tant qu’admin via le formulaire
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'Inatest Zaoui',
            '_password' => 'password',
        ]);
        $this->client->submit($form);
        $this->client->followRedirect();

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $blocked = $userRepo->findOneBy(['name' => 'Marie Durand']);
        $this->assertNotNull($blocked, 'Utilisateur bloqué introuvable.');

        $this->client->request('GET', '/guest/' . $blocked->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Marie Durand');
    }



    public function testPortfolioWithAlbumIdIsAccessible(): void
    {
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');
        $albumRepo = $registry->getRepository(\App\Entity\Album::class);

        $album = $albumRepo->findOneBy([]);
        $this->assertNotNull($album, 'Un album doit être présent en base.');

        $this->client->request('GET', '/portfolio/' . $album->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }



    public function testHomePageIsSuccessful(): void
    {
        
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectorExists('img'); // ou autre élément présent sur ta home
    }

    public function testGuestsPageIsSuccessful(): void
    {
        $crawler = $this->client->request('GET', '/guests');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('ul'); // adapte au design de ta page
    }
    public function testPortfolioPageForAdminIsSuccessful(): void
    {
        $admin = $this->getAdmin();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/portfolio');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body'); // adapte si tu as une classe spécifique
    }


    public function testAboutPageIsSuccessful(): void
    {
       
        $crawler = $this->client->request('GET', '/about');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('img'); // ou autre
    }

    public function testBlockedUserAlbumIsInaccessible(): void
{
    $this->client->loginUser($this->getIna());

    // On récupère explicitement l’utilisateur bloqué "Lucie Cromagnon"
    $blockedUser = $this->getDoctrine()
        ->getRepository(User::class)
        ->findOneBy(['name' => 'Lucie Cromagnon']);

    $this->assertNotNull($blockedUser);
    $this->assertTrue($blockedUser->isBlocked());

    // Puis un album lui appartenant
    $album = $this->getAlbumForUser($blockedUser);

    // Et on tente d’y accéder
    $this->client->request('GET', '/portfolio/' . $album->getId());

    // Le site doit refuser l’accès
    $this->assertResponseStatusCodeSame(404);
}


public function testAccessToAlbumOfBlockedUserReturns404(): void
{
    $this->client->loginUser($this->getIna());

    $blockedAlbum = $this->getDoctrine()
        ->getRepository(Album::class)
        ->findOneBy(['name' => 'Album Invite2_1']);

    $this->assertNotNull($blockedAlbum);
    $this->assertInstanceOf(Album::class, $blockedAlbum);
    $this->assertNotNull($blockedAlbum->getUser());
    $this->assertTrue($blockedAlbum->getUser()->isBlocked());

    $this->client->request('GET', '/portfolio/' . $blockedAlbum->getId());
    $this->assertResponseStatusCodeSame(404);
}


}