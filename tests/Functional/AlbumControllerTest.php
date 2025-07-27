<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Album;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AlbumControllerTest extends CustomWebTestCase
{
    private KernelBrowser $client;
    private \Doctrine\ORM\EntityManagerInterface $em;
    private \App\Repository\AlbumRepository $albumRepository;
    private \App\Repository\UserRepository $userRepository;

protected function setUp(): void
{
    parent::setUp();
    $this->client = static::createClient();
    $container = static::getContainer();

    $this->loadFixtures([
        \App\DataFixtures\UserFixtures::class,
        \App\DataFixtures\AlbumFixtures::class,
    ], $container);

    $this->em = $container->get('doctrine')->getManager();

    /** @var \App\Repository\AlbumRepository $albumRepo */
    $albumRepo = $this->em->getRepository(Album::class);
    /** @var \App\Repository\UserRepository $userRepo */
    $userRepo = $this->em->getRepository(User::class);

    $this->albumRepository = $albumRepo;
    $this->userRepository = $userRepo;

    self::assertInstanceOf(\App\Repository\AlbumRepository::class, $this->albumRepository);
    self::assertInstanceOf(\App\Repository\UserRepository::class, $this->userRepository);

    $this->loginIna($this->client);
}


    private function loginIna(KernelBrowser $client): void
    {
        $user = self::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(User::class)    
            ->findOneBy(['name' => 'Inatest Zaoui']);

        $this->assertNotNull($user, "L'utilisateur admin Ina doit exister en base de données.");
        $client->loginUser($user);
    }

    public function testIndex(): void
    {   
        $this->client->request('GET', '/admin/album');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
        $this->assertStringContainsString('Albums', $this->client->getResponse()->getContent());
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
        $album = new Album();
        $album->setName('Album original');
        $this->em->persist($album);
        $this->em->flush();

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
        $album = new Album();
        $album->setName('À supprimer');
        $this->em->persist($album);
        $this->em->flush();

        $id = $album->getId();

        $this->client->request('GET', "/admin/album/delete/$id");
        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();
        $this->assertSelectorTextNotContains('body', 'À supprimer');
    }
}