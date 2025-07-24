<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Classe de tests fonctionnels dédiée à la gestion des invités (admin).
 * Elle teste toutes les actions possibles depuis le contrôleur AdminGuestController.
 */
class AdminGuestControllerTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client; // Navigateur simulé pour les requêtes HTTP
    private \Doctrine\ORM\EntityManagerInterface $em;              // Gestionnaire d'entité Doctrine
    private UserRepository $userRepository;                        // Repo pour accéder facilement aux utilisateurs

    /**
     * Méthode appelée avant chaque test. Elle initialise les outils nécessaires.
     */
    protected function setUp(): void
    {
        $this->client = static::createClient(); // Démarre un navigateur simulé
        $container = static::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->userRepository = $this->em->getRepository(User::class);
    }

    /**
     * Connecte un utilisateur en le recherchant par son nom.
     */
    private function loginAsName(string $name): User
    {
        $user = $this->userRepository->findOneByName($name);
        $this->assertNotNull($user, "L'utilisateur avec le nom {$name} doit exister en base.");
        $this->client->loginUser($user);
        return $user;
    }

    /**
     * Vérifie qu’un admin peut accéder à la page de gestion des invités.
     */
    public function testAdminCanAccessGuestManagementPage(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $this->client->request('GET', '/admin/guests');

        $this->assertResponseIsSuccessful(); // Code 200 attendu
        $this->assertSelectorExists('h1'); // Présence d’un titre sur la page
        $this->assertStringContainsString('Gestion des invités', $this->client->getResponse()->getContent());
    }

    /**
     * Vérifie qu’un invité ne peut pas accéder à la page de gestion des invités.
     */
    public function testGuestCannotAccessGuestManagementPage(): void
    {
        $this->loginAsName('Jean Dupont');
        $this->client->request('GET', '/admin/guests');
        $this->assertResponseStatusCodeSame(403); // Accès refusé
    }

    /**
     * Vérifie que l’admin peut bloquer un invité.
     */
    public function testAdminCanBlockGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $guest = $this->userRepository->findOneByEmail('invite1@example.com');
        $this->assertNotNull($guest);

        // Réinitialise l’état du guest pour un test cohérent
        if ($guest->isBlocked()) {
            $guest->setIsBlocked(false);
            $this->em->flush();
        }

        $this->client->request('GET', '/admin/guests/' . $guest->getId() . '/toggle-block');
        $this->em->clear(); // force Doctrine à recharger les données

        $updatedGuest = $this->userRepository->find($guest->getId());
        $this->assertTrue($updatedGuest->isBlocked());
        $this->assertResponseRedirects('/admin/guests');
    }

    /**
     * Vérifie que l’admin peut débloquer un invité bloqué.
     */
    public function testAdminCanUnblockGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $guest = $this->userRepository->findOneByEmail('invite2@example.com');
        $this->assertNotNull($guest);

        if (!$guest->isBlocked()) {
            $guest->setIsBlocked(true);
            $this->em->flush();
        }

        $this->client->request('GET', '/admin/guests/' . $guest->getId() . '/toggle-block');
        $this->em->clear();

        $updatedGuest = $this->userRepository->find($guest->getId());
        $this->assertFalse($updatedGuest->isBlocked());
        $this->assertResponseRedirects('/admin/guests');
    }

    /**
     * Vérifie que l’admin peut créer un nouvel invité via le formulaire.
     */
    public function testAdminCanAddGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $crawler = $this->client->request('GET', '/admin/guests/new');
        $this->assertResponseIsSuccessful();

        // Génère un email unique pour éviter les conflits
        $uniqueId = uniqid();
        $email = 'testguest_' . $uniqueId . '@example.com';

        $form = $crawler->selectButton('Créer un invité')->form([
            'guest[name]' => 'Invité ' . $uniqueId,
            'guest[email]' => $email,
            'guest[password]' => 'password',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/guests');

        $this->em->clear();
        $createdGuest = $this->userRepository->findOneByEmail($email);

        $this->assertNotNull($createdGuest);
        $this->assertFalse($createdGuest->isAdmin());
        $this->assertFalse($createdGuest->isBlocked());

        // Nettoyage : supprime le guest après test
        $this->em->remove($createdGuest);
        $this->em->flush();
    }

    /**
     * Vérifie que l’admin peut supprimer un invité depuis l’interface.
     */
    public function testAdminCanDeleteGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');

        // Création d’un invité de test
        $guest = new User();
        $guest->setName('GuestToDelete');
        $guest->setEmail('delete_me_' . uniqid() . '@example.com');
        $guest->setPassword('password');
        $guest->setAdmin(false);
        $this->em->persist($guest);
        $this->em->flush();

        try {
            $crawler = $this->client->request('GET', '/admin/guests');
            $this->assertResponseIsSuccessful();

            // Soumet le formulaire de suppression de l’invité
            $form = $crawler->filter('form[action="/admin/guests/' . $guest->getId() . '/delete"]')->form();
            $this->client->submit($form);

            $this->assertResponseRedirects('/admin/guests');

            $this->em->clear();
            $deletedGuest = $this->userRepository->find($guest->getId());
            $this->assertNull($deletedGuest);
        } finally {
            // Sécurité : suppression forcée si erreur
            if ($this->em->contains($guest)) {
                $this->em->remove($guest);
                $this->em->flush();
            }
        }
    }

public function testToggleBlockWithInvalidIdReturns404(): void
{
    $this->loginAsName('Inatest Zaoui');
    $this->client->request('GET', '/admin/guests/999999/toggle-block'); // ID qui n'existe pas
    $this->assertResponseStatusCodeSame(404);
}

public function testGuestCannotDeleteGuest(): void
{
    $this->loginAsName('Jean Dupont');

    $guest = $this->userRepository->findOneByEmail('invite1@example.com');
    $this->assertNotNull($guest);

    $this->client->request('POST', '/admin/guests/' . $guest->getId() . '/delete');
    $this->assertResponseStatusCodeSame(403);
}

public function testDeletingNonExistentGuestReturns404(): void
{
    $this->loginAsName('Inatest Zaoui');
    $this->client->request('POST', '/admin/guests/999999/delete');
    $this->assertResponseStatusCodeSame(404);
}


    public function testAdminCannotAddGuestWithInvalidData(): void
{
    $this->loginAsName('Inatest Zaoui');
    $crawler = $this->client->request('GET', '/admin/guests/new');
    $form = $crawler->selectButton('Créer un invité')->form([
        'guest[name]' => '', // Nom vide
        'guest[email]' => 'bademail', // Mauvais format
        'guest[password]' => '',
    ]);
    $crawler = $this->client->submit($form);
    

    $this->assertSelectorExists('.invalid-feedback');

}

}
