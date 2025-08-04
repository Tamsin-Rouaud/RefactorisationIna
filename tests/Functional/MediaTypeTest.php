<?php

namespace App\Tests\Repository;

use App\Entity\Media;
use App\Tests\Functional\CustomWebTestCase;

class MediaTypeTest extends CustomWebTestCase
{
    public function testFormThrowsIfUserOptionIsMissing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('L’utilisateur doit être fourni dans les options.');

        $client = static::createClient();
        $ina = $this->getIna();
        $client->loginUser($ina);

        $crawler = $client->request('GET', '/media/add');

        /** @var \Symfony\Component\Form\FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $formFactory->create(\App\Form\MediaType::class, new Media(), [
            'is_admin' => false,
            'album_repository' => static::getContainer()->get(\App\Repository\AlbumRepository::class),
            // 'user' est volontairement omis ici
        ]);
    }

    public function testPreSubmitSkipsIfNoUserInData(): void
    {
        /** @var \Symfony\Component\Form\FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');

        $form = $formFactory->create(\App\Form\MediaType::class, new Media(), [
            'is_admin' => true,
            'user_repository' => static::getContainer()->get(\App\Repository\UserRepository::class),
            'album_repository' => static::getContainer()->get(\App\Repository\AlbumRepository::class),
        ]);

        $form->submit([
            'title' => 'Test',
            'album' => '',
        ]);

        $this->assertTrue($form->isSynchronized());
    }
}
