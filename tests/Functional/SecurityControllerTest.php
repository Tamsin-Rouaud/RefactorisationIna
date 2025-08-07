<?php

namespace App\Tests\Controller;


use App\Tests\Functional\CustomWebTestCase;



class SecurityControllerTest extends CustomWebTestCase
{
public function testLogoutRouteIsAccessible(): void
{
    $this->client->request('GET', '/logout');
    $this->assertResponseStatusCodeSame(302); // Redirection vers login
}

  public function testLogoutRouteRedirects(): void
{
    $user = $this->getAdmin();
    $this->client->loginUser($user);

    $this->client->request('GET', '/logout');

    $this->assertResponseRedirects(); // Symfony redirige automatiquement
}

    public function testLoginFormIsDisplayed(): void
{
    $crawler = $this->client->request('GET', '/login');

    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('form');
    $this->assertSelectorExists('input[name="_username"]');
    $this->assertSelectorExists('input[name="_password"]');
}


    public function testLoginSuccess(): void
{
    $crawler = $this->client->request('GET', '/login');

    $form = $crawler->selectButton('Connexion')->form([
        '_username' => 'Jean Dupont',
        '_password' => 'password',
    ]);
    $this->client->submit($form);

    $this->assertResponseRedirects('/');
    $this->client->followRedirect();
    $this->assertSelectorNotExists('.alert-danger');
}


    public function testLoginFailsWithBadPassword(): void
{
    $crawler = $this->client->request('GET', '/login');

    $form = $crawler->selectButton('Connexion')->form([
        '_username' => 'Jean Dupont',
        '_password' => 'wrongpass',
    ]);
    $this->client->submit($form);

    $this->assertResponseRedirects('/login');
    $this->client->followRedirect();
    $this->assertSelectorExists('.alert-danger');
}

    public function testBlockedUserCannotLogin(): void
{
    $crawler = $this->client->request('GET', '/login');

    $form = $crawler->selectButton('Connexion')->form([
        '_username' => 'Marie Durand',
        '_password' => 'password',
    ]);
    $this->client->submit($form);

    $this->assertResponseRedirects('/login');
    $this->client->followRedirect();
    $this->assertSelectorExists('.alert-danger');
}

    public function testLogoutRedirectsToLogIn(): void
{
    // Connexion
    $crawler = $this->client->request('GET', '/login');
    $form = $crawler->selectButton('Connexion')->form([
        '_username' => 'Jean Dupont',
        '_password' => 'password',
    ]);
    $this->client->submit($form);
    $this->assertResponseRedirects('/');
    $this->client->followRedirect();

    // Simuler clic sur le lien de déconnexion
    $this->client->request('GET', '/logout');
    $this->assertResponseRedirects('/login');
}

}
