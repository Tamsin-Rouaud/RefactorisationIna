<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Album;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AlbumControllerTest extends CustomWebTestCase
{
    private KernelBrowser $client;
    private \Doctrine\Persistence\ObjectManager $em;
    

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = $this->client->getContainer();

        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], $container);

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = $container->get('doctrine');
        $this->em = $registry->getManager();

        $this->loginIna($this->client);
    }

    private function loginIna(KernelBrowser $client): void
    {
        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $user = $registry->getManager()
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
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Albums', $content);

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
        $form['album[user]'] = $this->getIna()->getId();


        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'Nouvel album test');
    }

    public function testUpdateAlbum(): void
    {
        $ina = $this->getIna();

        $album = new Album();
        $album->setName('Album original');
        $album->setUser($ina);

        $this->em->persist($album);
        $this->em->flush();

        $crawler = $this->client->request('GET', "/admin/album/update/{$album->getId()}");
        $form = $crawler->selectButton('Modifier')->form();
        $form['album[name]'] = 'Titre mis à jour';
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Titre mis à jour');
    }


    public function testDeleteAlbum(): void
    {
        $ina = $this->getIna();

        $album = new Album();
        $album->setName('À supprimer');
        $album->setUser($ina);

        $this->em->persist($album);
        $this->em->flush();

        $this->client->request('GET', "/admin/album/delete/{$album->getId()}");
        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();
        $this->assertSelectorTextNotContains('body', 'À supprimer');
    }


}