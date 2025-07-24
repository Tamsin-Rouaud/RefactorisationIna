<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class SecurityControllerTest extends WebTestCase
{
    private static bool $fixturesLoaded = false;

    private function loadFixturesOnce(): void
    {
        if (self::$fixturesLoaded) {
            return;
        }

        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $loader = new Loader();
        $loader->addFixture(new UserFixtures(
            $container->get('security.user_password_hasher')
        ));

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->purge();
        $executor->execute($loader->getFixtures());

        self::$fixturesLoaded = true;
        self::ensureKernelShutdown();
    }

    public function testLoginFormIsDisplayed(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginSuccess(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'Jean Dupont',
            '_password' => 'password',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertSelectorNotExists('.alert-danger');
    }

    public function testLoginFailsWithBadPassword(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'Jean Dupont',
            '_password' => 'wrongpass',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testBlockedUserCannotLogin(): void
    {
        $this->loadFixturesOnce();

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'Marie Durand',
            '_password' => 'password',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testLogoutRedirectsToHomepage(): void
{
    $this->loadFixturesOnce();

    $client = static::createClient();

    // D'abord, se connecter
    $crawler = $client->request('GET', '/login');
    $form = $crawler->selectButton('Connexion')->form([
        '_username' => 'Jean Dupont',
        '_password' => 'password',
    ]);
    $client->submit($form);
    $this->assertResponseRedirects('/');
    $client->followRedirect();

    // Ensuite, accéder manuellement à /logout (simule le lien)
    $client->request('GET', '/logout');

    // Symfony redirige automatiquement après logout
    $this->assertResponseRedirects('/');
}

}
