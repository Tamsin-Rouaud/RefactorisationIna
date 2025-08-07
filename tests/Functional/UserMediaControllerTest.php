<?php

namespace App\Tests\Functional;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserMediaControllerTest extends CustomWebTestCase
{
    public function testAccessDeniedForGuest(): void
    {
        $this->client->request('GET', '/media/add');
        $this->assertResponseRedirects('/login');
    }

    public function testFormDisplaysForInvite(): void
    {
        $this->client->loginUser($this->getInvite());
        $crawler = $this->client->request('GET', '/media/add');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="media[title]"]');
        $this->assertSelectorExists('input[name="media[file]"]');
    }

    public function testUploadValidImage(): void
    {
        $this->client->loginUser($this->getInvite());
        $crawler = $this->client->request('GET', '/media/add');

        $form = $crawler->selectButton('Ajouter')->form();

        $this->client->submit($form, [
            'media[title]' => 'Photo Test',
            'media[album]' => $this->getAlbumForUser($this->getInvite())->getId(),
            'media[file]' => $this->createTempImage(),
        ]);

        $this->assertResponseRedirects('/');

        $medias = $this->getDoctrine()->getRepository(Media::class)->findBy(['title' => 'Photo Test']);
        $this->assertNotEmpty($medias);
        $this->assertStringContainsString('uploads/', $medias[0]->getPath());
    }

    public function testUploadWithoutFileUsesDefaultImage(): void
    {
        $this->client->loginUser($this->getInvite());
        $crawler = $this->client->request('GET', '/media/add');

        $form = $crawler->selectButton('Ajouter')->form();

        $this->client->submit($form, [
            'media[title]' => 'Sans Image',
            'media[album]' => $this->getAlbumForUser($this->getInvite())->getId(),
        ]);

        $this->assertResponseRedirects('/');

        $media = $this->getDoctrine()->getRepository(Media::class)->findOneBy(['title' => 'Sans Image']);
        $this->assertNotNull($media);
        $this->assertSame('uploads/default.jpg', $media->getPath());
    }

    public function testUploadInvalidMimeType(): void
    {
        $this->client->loginUser($this->getInvite());
        $crawler = $this->client->request('GET', '/media/add');

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'Not an image');

        $form = $crawler->selectButton('Ajouter')->form();

        $this->client->submit($form, [
            'media[title]' => 'Fichier invalide',
            'media[album]' => $this->getAlbumForUser($this->getInvite())->getId(),
            'media[file]' => new UploadedFile(
                $tempFile,
                'invalid.txt',
                'text/plain',
                null,
                true
            )
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.form-error-message', 'Seules les images JPEG, PNG ou GIF sont autorisées.');
    }

    public function testUploadOversizedFile(): void
    {
        $this->client->loginUser($this->getInvite());
        $crawler = $this->client->request('GET', '/media/add');

        $bigImage = tempnam(sys_get_temp_dir(), 'bigimg');
        file_put_contents($bigImage, str_repeat('a', 3 * 1024 * 1024)); // 3 Mo

        $form = $crawler->selectButton('Ajouter')->form();

        $this->client->submit($form, [
            'media[title]' => 'Image trop lourde',
            'media[album]' => $this->getAlbumForUser($this->getInvite())->getId(),
            'media[file]' => new UploadedFile(
                $bigImage,
                'big.jpg',
                'image/jpeg',
                null,
                true
            )
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.form-error-message', 'Le fichier ne doit pas dépasser 2 Mo.');
    }

    public function testUserCanAddMediaSuccessfully(): void
    {
        $invite = $this->getInvite(); 
        $this->client->loginUser($invite);

        $crawler = $this->client->request('GET', '/media/add');

        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Image ajoutée par user',
            'media[album]' => $this->getAlbumForUser($invite)->getId(),
        ]);
    /** @var \Symfony\Component\DomCrawler\Field\FileFormField $fileField */
    $fileField = $form->get('media[file]');
    $fileField->upload($this->createTempImage()->getPathname());


        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $media = $this->getDoctrine()->getRepository(Media::class)->findOneBy(['title' => 'Image ajoutée par user']);
        $this->assertNotNull($media);
    $this->assertNotNull($media->getUser(), 'Le média n’est pas associé à un utilisateur.');
    $this->assertSame($invite->getId(), $media->getUser()->getId());

    }



}

