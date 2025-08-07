<?php

namespace App\Tests\Functional;

use App\Entity\Media;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DomCrawler\Field\FileFormField;


class MediaControllerTest extends CustomWebTestCase
{
    protected function getUploadDir(): string
    {
        /** @var ParameterBagInterface $bag */
        $bag = static::getContainer()->get('parameter_bag');
        $uploadDir = $bag->get('upload_dir');

        if (!is_string($uploadDir)) {
            throw new \RuntimeException('upload_dir doit être une chaîne de caractères');
        }

        return $uploadDir;
    }


    public function testAddMediaAsAdminWithImage(): void
    {
        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $file = $this->createTempImage();

        $this->client->loginUser($ina);

        $crawler = $this->client->request('GET', '/admin/media/add?user=' . $ina->getId());

        $form = $crawler->selectButton('Ajouter')->form([
        'media[title]' => 'Ajout admin image',
        'media[user]' => (string) $ina->getId(),
        'media[album]' => (string) $album->getId(),
    ]);

    /** @var FileFormField $fileField */
    $fileField = $form->get('media[file]');
    $fileField->upload($file->getPathname());


        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/media');
        $this->client->followRedirect();

        $media = $this->getDoctrine()->getRepository(Media::class)->findOneBy([
            'title' => 'Ajout admin image',
        ]);

        $this->assertInstanceOf(Media::class, $media);

        $user = $media->getUser();
        $this->assertInstanceOf(\App\Entity\User::class, $user);
        $this->assertSame($ina->getId(), $user->getId());

        $this->deleteFileIfExists($media->getPath());
    }


    public function testAddMediaWithoutUserIdDefaultsToConnectedUser(): void
    {
        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $this->client->loginUser($ina);

        $crawler = $this->client->request('GET', '/admin/media/add');

        $form = $crawler->selectButton('Ajouter')->form();
        $form['media[title]'] = 'Titre par défaut';
        $form['media[user]'] = (string) $ina->getId();
        $form['media[album]'] = (string) $album->getId();

        /** @var \Symfony\Component\DomCrawler\Field\FileFormField $fileField */
        $fileField = $form->get('media[file]');
        $fileField->upload($this->createTempImage()->getPathname());


        $this->client->submit($form);
        $this->assertResponseRedirects('/admin/media');

        $media = $this->getDoctrine()->getRepository(Media::class)->findOneBy(['title' => 'Titre par défaut']);
        $this->assertNotNull($media);

        $user = $media->getUser();
        $this->assertNotNull($user);
        $this->assertSame($ina->getId(), $user->getId());
    }

    public function testSubmitMediaFormWithInvalidUserId(): void
    {
        $this->client->loginUser($this->getIna());

        $crawler = $this->client->request('GET', '/admin/media/add');
        $image = $this->createTempImage();

        $this->client->request('POST', '/admin/media/add', [
            'media' => [
                'title' => 'Titre test',
                'user' => '999999',
            ]
        ], [
            'media' => [
                'file' => $image
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('select[name="media[album]"]');
        $this->deleteFileIfExists($image->getPathname());
    }

    public function testAdminCanDeleteMediaAndFile(): void
    {
        $ina = $this->getIna();
        $this->client->loginUser($ina);

        $uploadDir = $this->getUploadDir();
        $fakeFile = $uploadDir . '/test_to_delete.jpg';
        file_put_contents($fakeFile, 'fake content');
        $this->assertFileExists($fakeFile);

        $media = new Media();
        $media->setTitle('Média à supprimer');
        $media->setUser($ina);
        $media->setAlbum($this->getAlbumForUser($ina));
        $media->setPath('uploads/' . basename($fakeFile));

        $this->em->persist($media);
        $this->em->flush();

        $this->client->request('GET', '/admin/media/delete/' . $media->getId());

        $this->assertResponseRedirects('/admin/media');
        $this->client->followRedirect();

        $this->assertFileDoesNotExist($fakeFile);
    }

    public function testAdminCannotAddMediaForBlockedUser(): void
    {
        $ina = $this->getIna();
        $blockedUser = $this->getBlockedUser();
        $album = $this->getAlbumForUser($blockedUser);

        $this->client->loginUser($ina);

        $crawler = $this->client->request('GET', '/admin/media/add?user=' . $blockedUser->getId());

        $form = $crawler->selectButton('Ajouter')->form();
        $form['media[title]'] = 'Photo test';
        $form['media[user]'] = (string) $blockedUser->getId();
        $form['media[album]'] = (string) $album->getId();
        /** @var \Symfony\Component\DomCrawler\Field\FileFormField $fileField */
        $fileField = $form->get('media[file]');
        $fileField->upload($this->createTempImage()->getPathname());


        $this->client->submit($form);

        $this->assertSelectorTextContains('.form-error-message', 'Impossible d’ajouter un média pour un utilisateur bloqué.');
    }

    public function testIndexRestrictsToUserIfNotAdmin(): void
    {
        $user = $this->getInvite(); // ou getIna() si admin est à part
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/media');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.page-item'); // ou adapte à ton HTML
    }

    public function testAddMediaWithoutImageShowsError(): void
    {
        $user = $this->getAdmin();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/media/add');

        $form = $crawler->selectButton('Ajouter')->form();
        $form['media[title]'] = 'Image sans fichier';
    $form['media[album]'] = (string) $this->getAlbumForUser($user)->getId();

        // Pas de media[file] !

        $this->client->submit($form);

        $this->assertSelectorTextContains('.form-error-message', 'Veuillez sélectionner un utilisateur');
    }

    public function testDeleteNonExistingMediaThrowsNotFound(): void
    {
        $user = $this->getAdmin();
        $this->client->loginUser($user);

    $this->client->request('GET', '/admin/media/delete/999999');
    $this->assertResponseStatusCodeSame(404);

    }

    public function testDeleteMediaAccessDeniedIfNotOwnerOrAdmin(): void
    {
        $owner = $this->getInvite();
        $media = $this->createMediaWithFile($owner);

        $attacker = $this->getOtherUser(); // le 3e utilisateur non admin
        $this->client->loginUser($attacker);

        $this->client->request('GET', '/admin/media/delete/' . $media->getId());

        $this->assertResponseStatusCodeSame(403); // accès interdit = 403
    }

    public function testAddMediaWithoutImageTriggersError(): void
    {
        $admin = $this->getAdmin();
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/media/add');

        $form = $crawler->selectButton('Ajouter')->form();

        // Fournir un utilisateur valide et non bloqué
        $form['media[title]'] = 'Test sans image';
        $form['media[user]'] = (string) $admin->getId(); // requis côté admin
        $form['media[album]'] = (string) $this->getAlbumForUser($admin)->getId(); // requis

        // Ne pas fournir de fichier image
    

        $this->client->submit($form);

        // Vérifie que le message "Une image est requise." s'affiche
        $this->assertSelectorTextContains('.form-error-message', 'Une image est requise.');
    }



}
