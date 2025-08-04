<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Functional\CustomWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends CustomWebTestCase
{

    private function createTempImageFile(): UploadedFile
    {
        $source = __DIR__ . '/fixtures/sample.jpg';

        // S’il n’existe pas, crée une vraie image JPEG vide
        if (!file_exists($source)) {
            imagejpeg(imagecreatetruecolor(1, 1), $source);
        }

        $target = sys_get_temp_dir() . '/test_' . uniqid() . '.jpg';
        copy($source, $target);

        return new UploadedFile($target, basename($target), 'image/jpeg', null, true);
    }


    private function deleteFileIfExists(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testInaCanAccessMediaIndex(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
            \App\DataFixtures\MediaFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $client->loginUser($ina);
        $client->request('GET', '/admin/media');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
    }


    public function testInviteSeesOnlyHisOwnMedias(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
            \App\DataFixtures\MediaFixtures::class,
        ], static::getContainer());

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);

        $invite = $userRepo->findOneBy(['name' => 'Jean Dupont']);
        $this->assertNotNull($invite);
        $client->loginUser($invite);

        $client->request('GET', '/admin/media');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Photo Invité Actif');
        $this->assertSelectorTextNotContains('body', 'Photo Ina 1');
    }


    public function testInaCanAddMedia(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        // 1. Charger le formulaire (initial, champ album vide)
        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        // 2. Simuler première soumission avec seulement l’utilisateur
        $crawler = $client->submit($form, [
            'media[user]' => $ina->getId(),
        ]);

        // 3. Nouveau formulaire généré après PRE_SUBMIT avec albums disponibles
        $form = $crawler->selectButton('Ajouter')->form();

        // Créer une image temporaire
        $file = $this->createTempImageFile();

        // 4. Soumettre avec tous les champs, y compris le fichier
        $client->submit($form, [
            'media[title]' => 'Image valide',
            'media[user]' => (string) $ina->getId(),
            'media[album]' => (string) $album->getId(),
        ], [
            'media[file]' => $file,
        ]);

        // 5. Vérifier que l’upload a réussi
        $this->assertResponseRedirects('/admin/media');
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Image valide');

        // Vérifier la présence du fichier sur le disque
        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->getDoctrine();
        $mediaRepo = $doctrine->getRepository(Media::class);
        $media = $mediaRepo->findOneBy(['title' => 'Image valide']);
        $this->assertNotNull($media);

        $uploadDir = static::getContainer()->getParameter('upload_dir');
        if (!is_string($uploadDir)) {
            $this->fail('Le paramètre "upload_dir" doit être une chaîne de caractères.');
        }

        $uploadPath = $uploadDir . '/' . basename($media->getPath());
        $this->assertFileExists($uploadPath);

        // Nettoyage du fichier temporaire
        $this->deleteFileIfExists($file->getPathname());
    }

    public function testInaCannotAddMediaWithoutTitle(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        // Formulaire initial
        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        // Étape intermédiaire : charger les albums
        $crawler = $client->submit($form, [
            'media[user]' => $ina->getId(),
        ]);

        $form = $crawler->selectButton('Ajouter')->form();

        // Envoi sans titre
        $client->submit($form, [
            'media[title]' => '',
            'media[user]' => $ina->getId(),
            'media[album]' => $album->getId(),
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('body', 'titre');
    }

    public function testInaCanAddMediaWithoutAlbum(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
        ], $container);

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = $container->get('doctrine');

        $ina = $registry->getRepository(User::class)->findOneBy(['name' => 'Inatest Zaoui']);
        $this->assertNotNull($ina);
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Sans album',
            'media[user]' => $ina->getId(), // obligatoire pour ROLE_ADMIN
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/media');
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Sans album');

        /** @var \App\Repository\MediaRepository $mediaRepo */
        $mediaRepo = $registry->getRepository(\App\Entity\Media::class);
        $media = $mediaRepo->findOneBy(['title' => 'Sans album']);
        $this->assertNotNull($media);
        $this->assertNull($media->getAlbum());
    }


    public function testInaCanAccessMediaAddForm(): void
    {
        $client = static::createClient();
        $ina = $this->getIna();
        $client->loginUser($ina);

        $client->request('GET', '/admin/media/add');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorTextContains('body', 'Image'); // ou autre label du formulaire
    }

    public function testInaCanDeleteFakeMedia(): void
    {
        $client = static::createClient();

        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');
        $em = $registry->getManager();
        if (!$em instanceof \Doctrine\ORM\EntityManagerInterface) {
            throw new \RuntimeException('Le manager Doctrine n’est pas un EntityManagerInterface.');
        }

        // Crée un fichier image temporaire
        $uploadDir = static::getContainer()->getParameter('upload_dir');
        self::assertIsString($uploadDir); // ✅ pour PHPStan

        $filename = 'test_delete_' . uniqid() . '.jpg';
        $path = $uploadDir . '/' . $filename;
        imagejpeg(imagecreatetruecolor(10, 10), $path);

        // Crée un média fictif
        $media = new Media();
        $media->setTitle('À supprimer');
        $media->setUser($ina);
        $media->setAlbum($album);
        $media->setPath('uploads/' . $filename);

        $em->persist($media);
        $em->flush();

        // Ajout d'une vérification de sécurité ici
        $mediaId = $media->getId();
        $this->assertNotNull($mediaId, 'Le média doit avoir un ID après flush.');


        // Appelle la route de suppression
        $client->request('GET', '/admin/media/delete/' . $mediaId);

        // Vérifie la redirection
        $this->assertResponseRedirects('/admin/media');

        /** @var \App\Repository\MediaRepository $mediaRepo */
        $mediaRepo = $registry->getRepository(Media::class);
        $deleted = $mediaRepo->find($mediaId);

        // Double sécurité ici
        $this->assertNull($deleted, 'Le média doit être supprimé de la base.');
        $this->assertFileDoesNotExist($path, 'Le fichier physique doit être supprimé.');
    }

    public function testDeleteInexistantMediaReturns404(): void
    {
        $client = static::createClient();
        $ina = $this->getIna();
        $client->loginUser($ina);

        $client->request('GET', '/admin/media/delete/99999'); // ID inexistant
        $this->assertResponseStatusCodeSame(404);
    }
        
    public function testInviteCannotDeleteMediaOfIna(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
            \App\DataFixtures\MediaFixtures::class,
        ], $container);

        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = $container->get('doctrine');

        /** @var \App\Repository\UserRepository $userRepo */
        $userRepo = $registry->getRepository(User::class);
        $invite = $userRepo->findOneBy(['name' => 'Jean Dupont']);

        /** @var \App\Repository\MediaRepository $mediaRepo */
        $mediaRepo = $registry->getRepository(Media::class);
        $media = $mediaRepo->findOneBy(['title' => 'Photo Ina 1']);

        $this->assertNotNull($invite);
        $this->assertNotNull($media);

        $client->loginUser($invite);
        $client->request('GET', '/admin/media/delete/' . $media->getId());

        $this->assertResponseStatusCodeSame(403);
    }


    public function testInaCanAddMediaWithoutImage(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        // Étape 1 : charger le formulaire vide
        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        // Étape 2 : soumettre seulement l’utilisateur pour déclencher PRE_SUBMIT
        $crawler = $client->submit($form, [
            'media[user]' => $ina->getId(),
        ]);

        // Étape 3 : récupérer le formulaire mis à jour avec les albums
        $form = $crawler->selectButton('Ajouter')->form();

        // Étape 4 : soumettre tous les champs sauf l’image
        $client->submit($form, [
            'media[title]' => 'Image absente',
            'media[user]' => $ina->getId(),
            'media[album]' => $album->getId(),
            // pas de media[file]
        ]);

        // Vérification du résultat
        $this->assertResponseRedirects('/admin/media');
        $client->followRedirect();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->getDoctrine();
        $mediaRepo= $doctrine->getRepository(Media::class);
        // $mediaRepo = static::getContainer()->get('doctrine')->getRepository(\App\Entity\Media::class);
        $media = $mediaRepo->findOneBy(['title' => 'Image absente']);

        $this->assertNotNull($media);
        $this->assertSame('uploads/default.jpg', $media->getPath());
    }

    public function testMediaAddFormWithUserIdInQuery(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $client->loginUser($ina);

        $client->request('GET', '/admin/media/add?user=' . $ina->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAdminSubmitWithoutUserThrowsError(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna(); // admin
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        $formData = $form->getPhpValues();
        $formData['media']['title'] = 'Test sans user';
        unset($formData['media']['user']);      // on simule l'oubli
        unset($formData['media']['album']);     // laisser vide pour éviter l’erreur

        $form->setValues($formData);
        $client->catchExceptions(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Veuillez sélectionner un utilisateur.');

        $client->submit($form);
    }


    public function testAdminCannotAddMediaForBlockedUser(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna(); // admin
        $client->loginUser($ina);


    /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
    $doctrine = static::getContainer()->get('doctrine');

    /** @var \App\Repository\UserRepository $userRepo */
    $userRepo = $doctrine->getRepository(User::class);

        $blockedUser = $userRepo->findOneBy(['isBlocked' => true]);
        $this->assertNotNull($blockedUser);

        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        $formData = $form->getPhpValues();
        $formData['media']['title'] = 'Test utilisateur bloqué';
        $formData['media']['user'] = $blockedUser->getId();

        $form->setValues($formData);

        $client->catchExceptions(false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible d’ajouter un média pour un utilisateur bloqué.');

        $client->submit($form);
    }


}
