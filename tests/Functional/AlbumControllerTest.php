<?php

namespace App\Tests\Functional;


use App\Entity\Album;

class AlbumControllerTest extends CustomWebTestCase
{
    public function testIndex(): void
    {
        $this->client->loginUser($this->getIna());
        $this->client->request('GET', '/admin/album');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
        $this->assertStringContainsString('Albums', (string) $this->client->getResponse()->getContent());
    }

    public function testAddAlbumFormDisplayed(): void
    {
        $this->client->loginUser($this->getIna());
        $crawler = $this->client->request('GET', '/admin/album/add');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAddAlbum(): void
    {
        $ina = $this->getIna();
        $this->client->loginUser($ina);

        $crawler = $this->client->request('GET', '/admin/album/add');
        $form = $crawler->selectButton('Ajouter')->form();
        $form['album[name]'] = 'Nouvel album test';
        $form['album[user]'] = (string) $ina->getId();

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();

        $this->assertSelectorTextContains('body', 'Nouvel album test');
    }

    public function testUpdateAlbum(): void
    {
        $ina = $this->getIna();
        $this->client->loginUser($ina);

        $album = new Album();
        $album->setName('Album original');
        $album->setUser($ina);

        $em = $this->getDoctrine()->getManager();
        $em->persist($album);
        $em->flush();

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
        $this->client->loginUser($ina);

        $album = new Album();
        $album->setName('À supprimer');
        $album->setUser($ina);

        $em = $this->getDoctrine()->getManager();
        $em->persist($album);
        $em->flush();

        $this->client->request('GET', "/admin/album/delete/{$album->getId()}");
        $this->assertResponseRedirects('/admin/album');
        $this->client->followRedirect();
        $this->assertSelectorTextNotContains('body', 'À supprimer');
    }

    public function testAddAlbumAsNonAdminSetsUserAutomatically(): void
    {
        $invite = $this->getInvite();
        $this->client->loginUser($invite);

        $crawler = $this->client->request('GET', '/admin/album/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'album[name]' => 'Album utilisateur simple',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/album');

        $repo = $this->getDoctrine()->getRepository(Album::class);
        $album = $repo->findOneBy(['name' => 'Album utilisateur simple']);
        $this->assertInstanceOf(Album::class, $album);
        $this->assertSame($invite->getId(), $album->getUser()?->getId());
    }

    public function testAddAlbumWithInvalidFormStaysOnPage(): void
    {
        $this->client->loginUser($this->getIna());

        $crawler = $this->client->request('GET', '/admin/album/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'album[name]' => '',
        ]);

        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateAlbumWithInvalidIdThrows404(): void
    {
        $this->client->loginUser($this->getIna());
        $this->client->request('GET', '/admin/album/update/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteAlbumWithInvalidIdThrows404(): void
    {
        $this->client->loginUser($this->getIna());
        $this->client->request('GET', '/admin/album/delete/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetAlbumsByUserReturnsJson(): void
    {
        $user = $this->getIna();

        $album = new Album();
        $album->setName('Album Ajax Test');
        $album->setUser($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($album);
        $em->flush();

        $this->client->loginUser($user); // sécurité potentielle
        $this->client->request('GET', '/admin/albums/by-user/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $json = $this->client->getResponse()->getContent();
        $this->assertIsString($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $this->assertContains('Album Ajax Test', array_column($data, 'name'));
    }

    public function testGetAlbumsByBlockedUserReturnsEmptyArray(): void
{
    $blockedUser = $this->getBlockedUser();
    $this->client->loginUser($this->getIna());

    $this->client->request('GET', '/admin/albums/by-user/' . $blockedUser->getId());

    $this->assertResponseIsSuccessful();
    $json = $this->client->getResponse()->getContent();
    $this->assertIsString($json); // 🔒 Vérifie que c'est bien une string

    $albums = json_decode($json, true);
    // on vérifie bien que le tableau est vide
    $this->assertSame([], $albums, 'Un utilisateur bloqué ne doit retourner aucun album');
}

public function testAddAlbumAsAdmin(): void
{
    $ina = $this->getIna();
    $this->client->loginUser($ina);

    $crawler = $this->client->request('GET', '/admin/album/add');
    $form = $crawler->selectButton('Ajouter')->form([
        'album[name]' => 'Album de test',
        'album[user]' => $ina->getId(),
    ]);

    $this->client->submit($form);
    $this->assertResponseRedirects('/admin/album');

    $album = $this->getDoctrine()->getRepository(Album::class)->findOneBy(['name' => 'Album de test']);
    $this->assertNotNull($album);
}

}
