<?php

namespace App\Tests\Functional;

use App\Controller\UserMediaController;
use App\Entity\Media;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserMediaControllerTest extends CustomWebTestCase
{
    private function createTempImage(): UploadedFile
    {
        $source = __DIR__ . '/fixtures/sample.jpg';
        if (!file_exists($source)) {
            imagejpeg(imagecreatetruecolor(1, 1), $source);
        }
        $target = sys_get_temp_dir() . '/img_' . uniqid() . '.jpg';
        copy($source, $target);
        return new UploadedFile($target, 'sample.jpg', 'image/jpeg', null, true);
    }

    public function testRedirectIfNotLoggedIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/media/add');
        $this->assertResponseRedirects('/login');
    }

    public function testUserCanAccessAddForm(): void
    {
        $client = static::createClient();
        $ina = $this->getIna();
        $client->loginUser($ina);
        $client->request('GET', '/media/add');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testUserCanAddMedia(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        $file = $this->createTempImage();

        $client->submit($form, [
            'media[title]' => 'Média utilisateur',
            'media[album]' => $album->getId(),
        ], [
            'media[file]' => $file,
        ]);

        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertSelectorNotExists('form');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        $repo = $doctrine->getRepository(Media::class);
        $media = $repo->findOneBy(['title' => 'Média utilisateur']);

        $this->assertNotNull($media);
        $this->assertInstanceOf(User::class, $media->getUser());
        $this->assertSame('Inatest Zaoui', $media->getUser()->getName());

        $uploadDir = static::getContainer()->getParameter('upload_dir');
        $this->assertIsString($uploadDir);
        $this->assertFileExists($uploadDir . '/' . basename($media->getPath()));
    }

    public function testUserCanAddMediaWithoutImage(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna();
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        $client->submit($form, [
            'media[title]' => 'Sans image',
            'media[album]' => $album->getId(),
        ]); // PAS de 'media[file]' ici

        $this->assertResponseRedirects('/');
        $client->followRedirect();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
    $doctrine = static::getContainer()->get('doctrine');

    /** @var \Doctrine\Persistence\ObjectRepository<\App\Entity\Media> $repo */
    $repo = $doctrine->getRepository(\App\Entity\Media::class);

        $media = $repo->findOneBy(['title' => 'Sans image']);

        $this->assertNotNull($media);
        $this->assertSame('uploads/default.jpg', $media->getPath());
    }


    public function testThrowsIfUserIsNotInstanceOfUserEntity(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Utilisateur connecté invalide.');

        $request = new \Symfony\Component\HttpFoundation\Request();

        /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params */
        $params = static::getContainer()->get(\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface::class);

        $controller = new UserMediaController($params);

        $controller->setContainer(static::getContainer());

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = static::getContainer()->get('security.token_storage');
        $fakeUser = new \App\Tests\Helper\FakeUser();

        $token = new UsernamePasswordToken(
            $fakeUser,
            'main',
            $fakeUser->getRoles()
        );
        $tokenStorage->setToken($token);

        $controller->add($request, $this->getDoctrine());
    }

    public function testNonAdminMediaCreationCallsSetUserAgain(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna(); // non-admin dans ton projet
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        $client->submit($form, [
            'media[title]' => 'ForceSetUser',
            'media[album]' => $album->getId(),
        ]);

        $this->assertResponseRedirects('/');

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
    $doctrine = static::getContainer()->get('doctrine');
    $repo = $doctrine->getRepository(Media::class);
    $media = $repo->findOneBy(['title' => 'ForceSetUser']);

    $this->assertNotNull($media);
    $this->assertInstanceOf(User::class, $media->getUser());
    $this->assertSame($ina->getId(), $media->getUser()->getId());

    }
    public function testImageUploadAndSetUserCalledForNonAdmin(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $ina = $this->getIna(); // utilisateur non admin
        $album = $this->getAlbumForUser($ina);
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/media/add');
    

    $form = $crawler->selectButton('Ajouter')->form();
    file_put_contents('form_debug.html', $crawler->html());


        $file = $this->createTempImage();

        $client->submit($form, [
            'media[title]' => 'Image Upload Test',
            'media[album]' => $album->getId(),
        ], [
            'media[file]' => $file,
        ]);

        $this->assertResponseRedirects('/');
        $client->followRedirect();

    /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
    $doctrine = static::getContainer()->get('doctrine');
    $repo = $doctrine->getRepository(Media::class);

        $media = $repo->findOneBy(['title' => 'Image Upload Test']);

        $this->assertNotNull($media);
        $this->assertInstanceOf(User::class, $media->getUser());
        $this->assertSame($ina->getId(), $media->getUser()->getId());

        $path = $media->getPath();

        
        $this->assertNotSame('/public/uploads/default.jpg', $path, 'Le fichier image n’a pas été uploadé correctement.');


        $uploadDir = static::getContainer()->getParameter('upload_dir');
    $this->assertIsString($uploadDir); // ✅ Pour rassurer PHPStan
    $this->assertFileExists($uploadDir . '/' . basename($path));

    }

    public function testImageUploadTriggersSetUserAndUpload(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $user = $this->getIna(); // utilisateur non-admin
        $album = $this->getAlbumForUser($user);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        // Générer un faux fichier image
        $filePath = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
        imagejpeg(imagecreatetruecolor(1, 1), $filePath);

        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $filePath,
            'fake.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        $client->submit($form, [
            'media[title]' => 'UploadedMedia',
            'media[album]' => $album->getId(),
        ], [
            'media[file]' => $uploadedFile,
        ]);

        $this->assertResponseRedirects('/');
        $client->followRedirect();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
    $doctrine = static::getContainer()->get('doctrine');
    $repo = $doctrine->getRepository(\App\Entity\Media::class);

        $media = $repo->findOneBy(['title' => 'UploadedMedia']);

        $this->assertNotNull($media);
        $this->assertSame($user->getId(), $media->getUser()?->getId());

        // ✅ Couvre le chemin avec fichier et la ligne 63
        $this->assertNotSame('public/uploads/default.jpg', $media->getPath(), 'Le fichier n’a pas été uploadé correctement.');



        $uploadDir = static::getContainer()->getParameter('upload_dir');
    $this->assertIsString($uploadDir); // <-- protège la concaténation
    $this->assertFileExists($uploadDir . '/' . basename($media->getPath()));

    }

    public function testUserAddMediaWithoutImageTriggersSetUserAndDefaultPath(): void
    {
        $client = static::createClient();
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
        ], static::getContainer());

        $user = $this->getIna(); // utilisateur non admin
        $album = $this->getAlbumForUser($user);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/media/add');
        $form = $crawler->selectButton('Ajouter')->form();

        $client->submit($form, [
            'media[title]' => 'MediaSansImage',
            'media[album]' => $album->getId(),
        ]);

        $this->assertResponseRedirects('/');
        $client->followRedirect();

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        $repo = $doctrine->getRepository(Media::class);
        $media = $repo->findOneBy(['title' => 'MediaSansImage']);

        $this->assertNotNull($media);
        $this->assertSame('uploads/default.jpg', $media->getPath()); // ✅ lignes 58–59
        $this->assertSame($user->getId(), $media->getUser()?->getId()); // ✅ ligne 63
    }


}





