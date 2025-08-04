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
        $form['album[user]'] = (string) $this->getIna()->getId();




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

    public function testAddAlbumAsNonAdminSetsUserAutomatically(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['name' => 'Jean Dupont']);
        self::assertInstanceOf(User::class, $user); // ✅ PHPStan comprend que $user n’est plus nullable

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/album/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'album[name]' => 'Album utilisateur simple',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/album');

        /** @var Album|null $album */
        $album = $this->em->getRepository(Album::class)->findOneBy(['name' => 'Album utilisateur simple']);
        self::assertInstanceOf(Album::class, $album); // ✅ plus sûr et plus clair

        $this->assertSame($user->getId(), $album->getUser()?->getId());
    }

    public function testAddAlbumWithInvalidFormStaysOnPage(): void
    {
        $crawler = $this->client->request('GET', '/admin/album/add');

        // On soumet uniquement le champ `name`, vide
        $form = $crawler->selectButton('Ajouter')->form([
            'album[name]' => '', // vide => invalide
            // NE FOURNIS PAS album[user] même si champ visible
        ]);

        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);

        
    }

    public function testUpdateAlbumWithInvalidIdThrows404(): void
    {
        $this->client->request('GET', '/admin/album/update/999999'); // ID inexistant
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteAlbumWithInvalidIdThrows404(): void
    {
        $this->client->request('GET', '/admin/album/delete/999999'); // ID très élevé qui n’existe pas
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetAlbumsByUserReturnsJson(): void
    {
        $user = $this->getIna(); // ou n’importe quel user non bloqué

        // On crée un album pour ce user
        $album = new Album();
        $album->setName('Album Ajax Test');
        $album->setUser($user);
        $this->em->persist($album);
        $this->em->flush();

        // Requête GET vers la route AJAX
        $this->client->request('GET', '/admin/albums/by-user/' . $user->getId());

        // Vérifications
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotEmpty($responseContent);

        $data = json_decode($responseContent, true);
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $albumNames = array_column($data, 'name');
        $this->assertContains('Album Ajax Test', $albumNames);

    }

    public function testGetAlbumsByBlockedUserReturnsEmptyArray(): void
    {
        $user = $this->getIna(); // Utilisateur non bloqué par défaut
        $user->setIsBlocked(true);
        $this->em->flush(); // Appliquer le blocage en base

        $this->client->request('GET', '/admin/albums/by-user/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $this->assertIsString($content); // assure PHPStan que ce n’est pas false
        $data = json_decode($content, true);

        $this->assertSame([], $data); // Vérifie que rien n'est retourné
    }

}