<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminGuestControllerTest extends WebTestCase
{
    public function testAdminCanAccessGuestManagementPage(): void
    {
        // Arrange
        $client = static::createClient();
        $container = static::getContainer();
        $ina = $container->get('doctrine')->getRepository(User::class)->findOneByEmail('ina@example.com');
        $client->loginUser($ina);

        // Act
        $client->request('GET', '/admin/guests');

        // Assert
        $this->assertResponseIsSuccessful(); // Vérifie code HTTP 200
        $this->assertSelectorExists('h1'); // Par exemple, s'il y a un <h1> sur la page
        $this->assertStringContainsString('Gestion des invités', $client->getResponse()->getContent());

    }

    public function testGuestCannotAccessGuestManagementPage(): void
    {
        // Arrange
        $client = static::createClient();
        $container = static::getContainer();
        $guest = $container->get('doctrine')->getRepository(User::class)->findOneByEmail('invite1@example.com');
        $client->loginUser($guest);

        // Act
        $client->request('GET', '/admin/guests');

        // Assert
        $this->assertResponseStatusCodeSame(403); // accès refusé
    }

}
