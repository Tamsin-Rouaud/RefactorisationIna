<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\Album;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;


class HomeControllerTest extends WebTestCase
{
    private static bool $fixturesLoaded = false;

    
    private function loadFixturesOnce(): void
    {
        if (self::$fixturesLoaded) {
            self::ensureKernelShutdown();
            return;
        }

        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $loader = new Loader();
        $loader->addFixture(new UserFixtures($container->get('security.user_password_hasher')));

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->purge();
        $executor->execute($loader->getFixtures());

        self::$fixturesLoaded = true;
        self::ensureKernelShutdown();
    }

    public function testHomePageIsAccessible(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testAboutPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/about');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testGuestsListShowsOnlyUnblockedUsers(): void
{
    $this->loadFixturesOnce();

    $client = static::createClient();
    $crawler = $client->request('GET', '/guests');

    $this->assertResponseIsSuccessful();

    $this->assertSelectorTextContains('body', 'Jean Dupont');
    $this->assertSelectorTextNotContains('body', 'Marie Durand'); 
}


    public function testGuestPageAccessibleIfNotBlocked(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $container = static::getContainer();
        $userRepo = $container->get('doctrine')->getRepository(User::class);
        $invite1 = $userRepo->findOneBy(['name' => 'Jean Dupont']);

        $client->request('GET', '/guest/' . $invite1->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Jean Dupont');
    }

    public function testGuestPageBlockedForNonAdmin(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $container = static::getContainer();
        $userRepo = $container->get('doctrine')->getRepository(User::class);
        $blocked = $userRepo->findOneBy(['name' => 'Marie Durand']);

        $client->request('GET', '/guest/' . $blocked->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGuestPageAccessibleForAdmin(): void
{
    $this->loadFixturesOnce();

    $client = static::createClient();

    // Connexion en tant qu’admin via le formulaire
    $crawler = $client->request('GET', '/login');
    $form = $crawler->selectButton('Connexion')->form([
        '_username' => 'Inatest Zaoui',
        '_password' => 'password',
    ]);
    $client->submit($form);
    $client->followRedirect();

    // Requête vers un invité bloqué
    $container = static::getContainer();
    $userRepo = $container->get('doctrine')->getRepository(User::class);
    $blocked = $userRepo->findOneBy(['name' => 'Marie Durand']);

    $client->request('GET', '/guest/' . $blocked->getId());

    $this->assertResponseIsSuccessful();
    $this->assertSelectorTextContains('body', 'Marie Durand');
}

public function testPortfolioPageShowsMediasForAdminUser(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $crawler = $client->request('GET', '/portfolio');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testPortfolioWithAlbumIdIsAccessible(): void
{
    $this->loadFixturesOnce();

    $client = static::createClient();
    $container = $client->getContainer(); // éviter bootKernel

    $album = $container->get('doctrine')->getRepository(Album::class)->findOneBy([]);
    if (!$album) {
        $this->markTestSkipped('Aucun album disponible pour tester.');
    }

    $client->request('GET', '/portfolio/' . $album->getId());

    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('body');
}

}