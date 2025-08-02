<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Entity\Album;
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

    // Étape 1 : afficher le formulaire
    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();

    // Étape 2 : simuler une première soumission partielle avec media[user]
    $form['media[user]']->setValue($ina->getId());
    $crawler = $client->submit($form);

    // Étape 3 : récupérer le nouveau formulaire avec les bons albums
    $form = $crawler->selectButton('Ajouter')->form();

    // Image temporaire valide
    $file = $this->createTempImageFile();
    $filePath = $file->getPathname();

    // Étape 4 : soumission complète
    $form['media[title]'] = 'Image valide';
    $form['media[user]']->setValue($ina->getId());
    $form['media[album]']->setValue($album->getId());
    $form['media[file]']->upload($filePath);

    $client->submit($form);

    // Vérification
    $this->assertResponseRedirects('/admin/media');
    $client->followRedirect();
    $this->assertSelectorTextContains('body', 'Image valide');

    // Vérifie que le fichier a bien été uploadé
    $mediaRepo = static::getContainer()->get('doctrine')->getRepository(Media::class);
    $media = $mediaRepo->findOneBy(['title' => 'Image valide']);
    $this->assertNotNull($media);

    $uploadDir = static::getContainer()->getParameter('upload_dir');
    $uploadPath = $uploadDir . '/' . basename($media->getPath());
    $this->assertFileExists($uploadPath);

    // Nettoyage
    $this->deleteFileIfExists($uploadPath);
    $this->deleteFileIfExists($filePath);
}




    public function testInaCannotAddNonImageFile(): void
{
    $client = static::createClient();
    $this->loadFixtures([
        \App\DataFixtures\UserFixtures::class,
        \App\DataFixtures\AlbumFixtures::class,
    ], static::getContainer());

    $ina = $this->getIna();
    $album = $this->getAlbumForUser($ina);
    $client->loginUser($ina);

    // Étape 1 : afficher le formulaire
    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[user]']->setValue($ina->getId());
    $crawler = $client->submit($form);

    // Étape 2 : recharger avec les albums disponibles
    $form = $crawler->selectButton('Ajouter')->form();

    // Crée un faux fichier texte
    $path = sys_get_temp_dir() . '/fake.txt';
    file_put_contents($path, 'not an image');
    $file = new UploadedFile($path, 'fake.txt', 'text/plain', null, true);

    $form['media[title]'] = 'Fichier non image';
    $form['media[user]']->setValue($ina->getId());
    $form['media[album]']->setValue($album->getId());
    $form['media[file]']->upload($file->getPathname());

    $client->submit($form);

    $this->assertResponseStatusCodeSame(200);
    $this->assertSelectorTextContains('body', 'Seules les images JPEG, PNG ou GIF sont autorisées.');

    $this->deleteFileIfExists($path);
}


    public function testInaCannotAddTooLargeImage(): void
{
    $client = static::createClient();
    $this->loadFixtures([
        \App\DataFixtures\UserFixtures::class,
        \App\DataFixtures\AlbumFixtures::class,
    ], static::getContainer());

    $ina = $this->getIna();
    $album = $this->getAlbumForUser($ina);
    $client->loginUser($ina);

    // Étape 1 : charger le formulaire et soumettre le champ user
    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[user]']->setValue($ina->getId());
    $crawler = $client->submit($form);

    // Étape 2 : recharger le formulaire avec les albums disponibles
    $form = $crawler->selectButton('Ajouter')->form();

    // Crée une image trop grande (3 Mo)
    $targetPath = sys_get_temp_dir() . '/uploaded_big_image.jpg';
    file_put_contents($targetPath, str_repeat('a', 3 * 1024 * 1024)); // 3 Mo
    $uploadedFile = new UploadedFile($targetPath, 'uploaded_big_image.jpg', 'image/jpeg', null, true);

    // Remplir et soumettre le formulaire
    $form['media[title]'] = 'Image trop lourde';
    $form['media[user]']->setValue($ina->getId());
    $form['media[album]']->setValue($album->getId());
    $form['media[file]']->upload($uploadedFile->getPathname());

    $client->submit($form);

    $this->assertResponseStatusCodeSame(200);
    $this->assertSelectorTextContains('body', 'Le fichier ne doit pas dépasser 2 Mo.');

    unlink($targetPath);
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

    // Étape 1 : charger et soumettre le champ user
    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[user]']->setValue($ina->getId());
    $crawler = $client->submit($form);

    // Étape 2 : recharger avec les albums
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[title]'] = ''; // vide
    $form['media[user]']->setValue($ina->getId());
    $form['media[album]']->setValue($album->getId());

    $client->submit($form);

    $this->assertResponseStatusCodeSame(200);
    $this->assertSelectorTextContains('.invalid-feedback', 'titre');
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

    // Étape 1 : charger le formulaire et soumettre le champ user
    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[user]']->setValue($ina->getId());
    $crawler = $client->submit($form);

    // Étape 2 : soumettre l’image sans fichier, avec titre et album valides
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[title]'] = 'Image absente';
    $form['media[user]']->setValue($ina->getId());
    $form['media[album]']->setValue($album->getId());

    $client->submit($form);
    $this->assertResponseRedirects('/admin/media');
    $client->followRedirect();

    /** @var \Doctrine\Persistence\ManagerRegistry $registry */
    $registry = static::getContainer()->get('doctrine');
    $mediaRepo = $registry->getRepository(\App\Entity\Media::class);
    $media = $mediaRepo->findOneBy(['title' => 'Image absente']);

    $this->assertNotNull($media);
    $this->assertSame('uploads/default.jpg', $media->getPath());
}



}
