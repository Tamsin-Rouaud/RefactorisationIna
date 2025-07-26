<?php

namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminGuestControllerTest extends CustomWebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->loadFixtures([
            UserFixtures::class,
        ], $container);

        $this->em = $container->get('doctrine')->getManager();
        /** @var UserRepository $repo */
$repo = self::getContainer()->get(UserRepository::class);
$this->userRepository = $repo;

    }

    private function loginAsName(string $name): User
    {
        $user = $this->userRepository->findOneBy(['name' => $name]);
        $this->assertNotNull($user, "L'utilisateur {$name} doit exister.");
        $this->client->loginUser($user);
        return $user;
    }

    public function testAdminCanAccessGuestManagementPage(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $this->client->request('GET', '/admin/guests');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
        $this->assertStringContainsString('Gestion des invités', $this->client->getResponse()->getContent());
    }

    public function testGuestCannotAccessGuestManagementPage(): void
    {
        $this->loginAsName('Jean Dupont');
        $this->client->request('GET', '/admin/guests');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanBlockGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $guest = $this->userRepository->findOneBy(['email' => 'invite1@example.com']);
        $this->assertNotNull($guest);
        $guest->setIsBlocked(false);
        $this->em->flush();

        $this->client->request('GET', '/admin/guests/' . $guest->getId() . '/toggle-block');
        $this->em->clear();

        $updated = $this->userRepository->find($guest->getId());
        $this->assertTrue($updated->isBlocked());
        $this->assertResponseRedirects('/admin/guests');
    }

    public function testAdminCanUnblockGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $guest = $this->userRepository->findOneBy(['email' => 'invite2@example.com']);
        $this->assertNotNull($guest);
        $guest->setIsBlocked(true);
        $this->em->flush();

        $this->client->request('GET', '/admin/guests/' . $guest->getId() . '/toggle-block');
        $this->em->clear();

        $updated = $this->userRepository->find($guest->getId());
        $this->assertFalse($updated->isBlocked());
        $this->assertResponseRedirects('/admin/guests');
    }

    public function testAdminCanAddGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $crawler = $this->client->request('GET', '/admin/guests/new');
        $this->assertResponseIsSuccessful();

        $email = 'guest_' . uniqid() . '@example.com';

        $form = $crawler->selectButton('Créer un invité')->form([
            'guest[name]' => 'Invité Test',
            'guest[email]' => $email,
            'guest[password]' => 'password',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/guests');
        $this->em->clear();
        $created = $this->userRepository->findOneBy(['email' => $email]);
        $this->assertNotNull($created);
        $this->assertFalse($created->isAdmin());
    }

    public function testAdminCanDeleteGuest(): void
    {
        $this->loginAsName('Inatest Zaoui');

        $guest = new User();
        $guest->setName('GuestToDelete');
        $guest->setEmail('delete_' . uniqid() . '@example.com');
        $guest->setPassword('password');
        $guest->setAdmin(false);
        $this->em->persist($guest);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/admin/guests');
        $form = $crawler->filter('form[action="/admin/guests/' . $guest->getId() . '/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/guests');
        $this->em->clear();
        $this->assertNull($this->userRepository->find($guest->getId()));
    }

    public function testToggleBlockWithInvalidIdReturns404(): void
    {
        $this->loginAsName('Inatest Zaoui');
        $this->client->request('GET', '/admin/guests/999999/toggle-block');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGuestCannotDeleteGuest(): void
    {
        $this->loginAsName('Jean Dupont');
        $guest = $this->userRepository->findOneBy(['email' => 'invite1@example.com']);
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
            'guest[name]' => '',
            'guest[email]' => 'bademail',
            'guest[password]' => '',
        ]);
        $this->client->submit($form);

        $this->assertSelectorExists('.invalid-feedback');
    }
}
