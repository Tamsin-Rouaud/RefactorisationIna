<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\Functional\CustomWebTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;


class HomeControllerTest extends CustomWebTestCase
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

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = $container->get('doctrine');

        $em = $registry->getManager();

        if (!$em instanceof \Doctrine\ORM\EntityManagerInterface) {
            throw new \RuntimeException('Le manager Doctrine n’est pas un EntityManagerInterface.');
        }

        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher */
        $hasher = $container->get('security.user_password_hasher');

        $loader = new Loader();
        $loader->addFixture(new UserFixtures($hasher));

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

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $invite1 = $userRepo->findOneBy(['name' => 'Jean Dupont']);

        $this->assertNotNull($invite1);
        $client->request('GET', '/guest/' . $invite1->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Jean Dupont');
    }


    public function testGuestPageBlockedForNonAdmin(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $blocked = $userRepo->findOneBy(['name' => 'Marie Durand']);
        $this->assertNotNull($blocked, 'Utilisateur bloqué introuvable.');

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

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $blocked = $userRepo->findOneBy(['name' => 'Marie Durand']);
        $this->assertNotNull($blocked, 'Utilisateur bloqué introuvable.');

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
        $client = static::createClient(); // Doit venir AVANT
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');
        $albumRepo = $registry->getRepository(\App\Entity\Album::class);

        $album = $albumRepo->findOneBy([]);
        $this->assertNotNull($album, 'Un album doit être présent en base.');

        $client->request('GET', '/portfolio/' . $album->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

}