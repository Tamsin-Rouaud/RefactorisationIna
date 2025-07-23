<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Album;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AlbumControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->loginIna($this->client);
    }

    private function loginIna(KernelBrowser $client): void
    {
        $user = self::getContainer()
            ->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['name' => 'Inatest Zaoui']); // adapte selon ta fixture

        $client->loginUser($user);
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', '/admin/album');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // ou adapte avec 'Liste des albums'
    }

    public function testAddAlbumFormDisplayed(): void
    {
        $crawler = $this->client->request('GET', '/admin/album/add');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAddAlbum(): void
    {
        $crawler = $this->client->request('GET', '/admin/album/add');

        $form = $crawler->selectButton('Ajouter')->form();
        $form['album[name]'] = 'Nouvel album test';

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'Nouvel album test');
    }

    public function testUpdateAlbum(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $album = new Album();
        $album->setName('Album original');
        $entityManager->persist($album);
        $entityManager->flush();

        $id = $album->getId();

        $crawler = $this->client->request('GET', "/admin/album/update/$id");

        $form = $crawler->selectButton('Modifier')->form();
        $form['album[name]'] = 'Titre mis à jour';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Titre mis à jour');
    }

    public function testDeleteAlbum(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $album = new Album();
        $album->setName('À supprimer');
        $entityManager->persist($album);
        $entityManager->flush();

        $id = $album->getId();

        $this->client->request('GET', "/admin/album/delete/$id");

        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();
        $this->assertSelectorTextNotContains('body', 'À supprimer');
    }
}
