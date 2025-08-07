<?php

namespace App\Tests\Functional;

use App\Entity\User;

class AdminGuestControllerTest extends CustomWebTestCase
{
    private function loginAsName(string $name): User
    {
        /** @var \App\Repository\UserRepository $repo */
        $repo = $this->getDoctrine()->getRepository(User::class);
        $user = $repo->findOneBy(['name' => $name]);
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
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Gestion des invités', $content);
    }

    public function testGuestCannotAccessGuestManagementPage(): void
    {
        $this->loginAsName('Jean Dupont');
        $this->client->request('GET', '/admin/guests');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanBlockGuest(): void
    {
        $admin = $this->loginAsName('Inatest Zaoui');

        $repo = $this->getDoctrine()->getRepository(User::class);
        $guest = $repo->findOneBy(['email' => 'invite1@example.com']);
        $this->assertInstanceOf(User::class, $guest);

        $guest->setIsBlocked(false);
        $this->getDoctrine()->getManager()->flush();

        $this->client->request('GET', '/admin/guests/' . $guest->getId() . '/toggle-block');
        $this->getDoctrine()->getManager()->clear();

        $updated = $repo->find($guest->getId());
        $this->assertInstanceOf(User::class, $updated);
        $this->assertTrue($updated->isBlocked());

        $this->assertResponseRedirects('/admin/guests');
    }

    public function testAdminCanUnblockGuest(): void
    {
        $admin = $this->loginAsName('Inatest Zaoui');

        $repo = $this->getDoctrine()->getRepository(User::class);
        $guest = $repo->findOneBy(['email' => 'invite2@example.com']);
        $this->assertInstanceOf(User::class, $guest);

        $guest->setIsBlocked(true);
        $this->getDoctrine()->getManager()->flush();

        $this->client->request('GET', '/admin/guests/' . $guest->getId() . '/toggle-block');
        $this->getDoctrine()->getManager()->clear();

        $updated = $repo->find($guest->getId());
        $this->assertInstanceOf(User::class, $updated);
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
        $this->getDoctrine()->getManager()->clear();

        $created = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $created);
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

        $em = $this->getDoctrine()->getManager();
        $em->persist($guest);
        $em->flush();

        $crawler = $this->client->request('GET', '/admin/guests');
        $form = $crawler->filter('form[action="/admin/guests/' . $guest->getId() . '/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/guests');
        $em->clear();

        $this->assertNull(
            $this->getDoctrine()->getRepository(User::class)->find($guest->getId())
        );
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
        $guest = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => 'invite1@example.com']);
        $this->assertInstanceOf(User::class, $guest);

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

        $this->assertSelectorTextContains('body', 'Le mot de passe est obligatoire.');
        $this->assertSelectorTextContains('body', 'Le nom est obligatoire.');
        $this->assertSelectorTextContains('body', 'L’email est invalide.');
    }
}
