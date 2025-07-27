<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Entity\Album;
use App\Tests\Functional\CustomWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends CustomWebTestCase
{
    private function getIna(): User
{
    /** @var \Doctrine\Persistence\ManagerRegistry $registry */
    $registry = static::getContainer()->get('doctrine');
    /** @var \App\Repository\UserRepository $repo */
    $repo = $registry->getRepository(User::class);
    $ina = $repo->findOneBy(['name' => 'Inatest Zaoui']);
    $this->assertNotNull($ina);
    $this->assertTrue($ina->isAdmin());
    return $ina;
}


   private function getAlbumForUser(User $user): Album
{
    /** @var \Doctrine\Persistence\ManagerRegistry $registry */
    $registry = static::getContainer()->get('doctrine');
    /** @var \App\Repository\AlbumRepository $repo */
    $repo = $registry->getRepository(Album::class);
    $album = $repo->findOneBy(['user' => $user]);
    $this->assertNotNull($album);
    return $album;
}

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

        $client->loginUser($this->getIna());
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
    $ina = $this->getIna();
    $album = $this->getAlbumForUser($ina);
    $client->loginUser($ina);

    $file = $this->createTempImageFile();
    $filePath = $file->getPathname();

    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form([
        'media[title]' => 'Image valide',
        'media[album]' => (string) $album->getId(),
    ]);

    $field = $form['media[file]'] ?? null;
    if (is_array($field)) {
        $field = reset($field);
    }
    if ($field instanceof \Symfony\Component\DomCrawler\Field\FileFormField) {
        $field->upload($filePath);
    }

    $client->submit($form);

    $this->assertResponseRedirects('/admin/media');

    /** @var \Doctrine\Persistence\ManagerRegistry $registry */
    $registry = static::getContainer()->get('doctrine');
    /** @var \App\Repository\MediaRepository $mediaRepo */
    $mediaRepo = $registry->getRepository(Media::class);

    $media = $mediaRepo->findOneBy(['title' => 'Image valide']);
    $this->assertNotNull($media);

    $mediaPath = $media->getPath();

    $uploadDir = static::getContainer()->getParameter('upload_dir');
    self::assertIsString($uploadDir); // ✅ pour PHPStan

    $uploadPath = $uploadDir . '/' . basename($mediaPath);

    $this->assertFileExists($uploadPath);

    $this->deleteFileIfExists($uploadPath);
    $this->deleteFileIfExists($filePath);
}



public function testInaCannotAddNonImageFile(): void
{
    $client = static::createClient();
    $ina = $this->getIna();
    $album = $this->getAlbumForUser($ina);
    $client->loginUser($ina);

    $path = sys_get_temp_dir() . '/fake.txt';
    file_put_contents($path, 'not an image');
    $file = new UploadedFile($path, 'fake.txt', 'text/plain', null, true);
    $filePath = $file->getPathname();

    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form([
        'media[title]' => 'Fichier non image',
        'media[album]' => $album->getId(),
    ]);

    $field = $form['media[file]'] ?? null;
    if (is_array($field)) {
        $field = reset($field);
    }
    if ($field instanceof \Symfony\Component\DomCrawler\Field\FileFormField) {
        $field->upload($filePath);
    }

    $client->submit($form);

    $this->assertResponseStatusCodeSame(200);
    $this->assertSelectorTextContains('body', 'Seules les images JPEG, PNG ou GIF sont autorisées.');

    $this->deleteFileIfExists($path);
}

public function testInaCannotAddTooLargeImage(): void
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
    $ina = $userRepo->findOneBy(['name' => 'Inatest Zaoui']);

    /** @var \App\Repository\AlbumRepository $albumRepo */
    $albumRepo = $registry->getRepository(Album::class);
    $album = $albumRepo->findOneBy(['user' => $ina]);

    $this->assertNotNull($ina);
    $this->assertNotNull($album);

    $client->loginUser($ina);

    $targetPath = sys_get_temp_dir() . '/uploaded_big_image.jpg';
    file_put_contents($targetPath, str_repeat('a', 3 * 1024 * 1024)); // 3 Mo

    $uploadedFile = new UploadedFile($targetPath, 'uploaded_big_image.jpg', 'image/jpeg', null, true);
    $filePath = $uploadedFile->getPathname();

    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[title]'] = 'Image trop lourde';
    $albumId = $album->getId();
$this->assertNotNull($albumId);
$form['media[album]'] = (string) $album->getId();


    $field = $form['media[file]'] ?? null;
    if (is_array($field)) {
        $field = reset($field);
    }
    if ($field instanceof \Symfony\Component\DomCrawler\Field\FileFormField) {
        $field->upload($filePath);
    }

    $client->submit($form);

    $this->assertResponseStatusCodeSame(200);
    $this->assertSelectorTextContains('body', 'Le fichier ne doit pas dépasser 2 Mo.');

    if (file_exists($targetPath)) {
        unlink($targetPath);
    }
}


    public function testInaCannotAddMediaWithoutTitle(): void
    {
        $client = static::createClient();
        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => '',
            'media[album]' => $album->getId(),
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('.invalid-feedback', 'titre');
    }

public function testInaCannotAddMediaWithoutAlbum(): void
{
    $client = static::createClient();
    $container = static::getContainer();

    $this->loadFixtures([
        \App\DataFixtures\UserFixtures::class,
    ], $container);

    /** @var \Doctrine\Persistence\ManagerRegistry $registry */
    $registry = $container->get('doctrine');

    /** @var \App\Repository\UserRepository $userRepo */
    $userRepo = $registry->getRepository(User::class);
    $ina = $userRepo->findOneBy(['name' => 'Inatest Zaoui']);
    $this->assertNotNull($ina);

    $client->loginUser($ina);

    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[title]'] = 'Sans album';
    // ne renseigne pas l’album
    $client->submit($form);

    $this->assertResponseStatusCodeSame(200);
    $this->assertSelectorTextContains('.invalid-feedback', 'obligatoire');

    // Sécurité : vérifie qu'aucun média n'a été créé
    /** @var \App\Repository\MediaRepository $mediaRepo */
    $mediaRepo = $registry->getRepository(\App\Entity\Media::class);
    $media = $mediaRepo->findOneBy(['title' => 'Sans album']);
    $this->assertNull($media, 'Aucun média ne doit être enregistré sans album.');
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
    $mediaId = $media->getId();

    // Appelle la route de suppression
    $client->request('GET', '/admin/media/delete/' . $mediaId);

    // Vérifie la redirection
    $this->assertResponseRedirects('/admin/media');

    /** @var \Doctrine\Persistence\ManagerRegistry $registry2 */
    $registry2 = static::getContainer()->get('doctrine');
    /** @var \App\Repository\MediaRepository $mediaRepo */
    $mediaRepo = $registry2->getRepository(Media::class);
    $deleted = $mediaRepo->find($mediaId);

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
    $container = static::getContainer();

    $this->loadFixtures([
        \App\DataFixtures\UserFixtures::class,
        \App\DataFixtures\AlbumFixtures::class,
    ], $container);

    /** @var \Doctrine\Persistence\ManagerRegistry $registry */
    $registry = $container->get('doctrine');

    /** @var \App\Repository\UserRepository $userRepo */
    $userRepo = $registry->getRepository(User::class);
    $ina = $userRepo->findOneBy(['name' => 'Inatest Zaoui']);
    $this->assertNotNull($ina);

    /** @var \App\Repository\AlbumRepository $albumRepo */
    $albumRepo = $registry->getRepository(\App\Entity\Album::class);
    $album = $albumRepo->findOneBy(['user' => $ina]);
    $this->assertNotNull($album);

    $client->loginUser($ina);

    $crawler = $client->request('GET', '/admin/media/add');
    $form = $crawler->selectButton('Ajouter')->form();
    $form['media[title]'] = 'Image absente';
    $form['media[album]'] = (string) $album->getId(); // cast pour éviter erreur PHPStan

    $client->submit($form);

    $this->assertResponseRedirects('/admin/media');
    $client->followRedirect();

    /** @var \App\Repository\MediaRepository $mediaRepo */
    $mediaRepo = $registry->getRepository(Media::class);
    $media = $mediaRepo->findOneBy(['title' => 'Image absente']);

    $this->assertNotNull($media);

    $path = $media->getPath();

    $this->assertSame('uploads/default.jpg', $path);
}


}
