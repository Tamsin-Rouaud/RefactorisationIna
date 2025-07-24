<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Album;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Classe de tests fonctionnels dédiée à la gestion des albums.
 * Elle teste toutes les actions possibles depuis le contrôleur AlbumController.
 */
class AlbumControllerTest extends WebTestCase
{
    
    private KernelBrowser $client;
    private \Doctrine\ORM\EntityManagerInterface $em;
    private \App\Repository\AlbumRepository $albumRepository;
    private \App\Repository\UserRepository $userRepository;
    /**
     * Méthode appelée avant chaque test. Elle initialise les outils nécessaires.
     */
    protected function setUp(): void
    {
        // Appel de la méthode parent pour initialiser le client et l'EntityManager
        parent::setUp();
        // Création du client pour simuler les requêtes HTTP
        $this->client = static::createClient();
        // Récupération du gestionnaire d'entité Doctrine
        $container = static::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        // Récupération des repositories pour les entités Album et User
        $this->albumRepository = $this->em->getRepository(Album::class);
        $this->userRepository = $this->em->getRepository(User::class);  
        // Connexion de l'utilisateur admin Ina pour les tests
        $this->loginIna($this->client);
    }
    /**
     * Connecte l'utilisateur admin Ina pour les tests.
     * Assure que l'utilisateur existe en base de données.
     */
    private function loginIna(KernelBrowser $client): void
    {
        // Récupération de l'utilisateur admin Ina depuis la base de données
        /** @var User $user */
        $user = self::getContainer()

            ->get('doctrine')
            ->getManager()
            ->getRepository(User::class)    
            ->findOneBy(['name' => 'Inatest Zaoui']); // adapte selon ta fixture

        // Vérification que l'utilisateur existe
        $this->assertNotNull($user, "L'utilisateur admin Ina doit exister en base de données.");
        // Connexion de l'utilisateur au client pour les tests
        $client->loginUser($user);
    }

    /**
     * Teste l'affichage de la liste des albums.
     */
    public function testIndex(): void
    {   
        // Envoi d'une requête GET à la route /admin/album
        $this->client->request('GET', '/admin/album');
        // Vérification que la réponse est réussie (code 200)
        $this->assertResponseIsSuccessful();
        // Vérification de la présence d'un titre sur la page
        $this->assertSelectorExists('h1');
        // Vérification que le titre contient "Albums"
        $this->assertStringContainsString('Albums', $this->client->getResponse()->getContent());

        $crawler = $this->client->request('GET', '/admin/album');
        // Vérification que la liste des albums est affichée
        

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
