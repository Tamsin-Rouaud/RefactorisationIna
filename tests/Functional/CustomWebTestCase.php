<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Entity\Album;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class CustomWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected \Doctrine\Persistence\ObjectManager $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->getDoctrine()->getManager();

        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
            \App\DataFixtures\MediaFixtures::class,
        ], static::getContainer());
    }

protected function getOtherUser(): User
{
    /** @var \App\Repository\UserRepository $userRepository */
$userRepository = self::getContainer()->get(UserRepository::class);


    $user = $userRepository->findOneBy(['name' => 'René Lataupe']); // ← adapte ici
    if (!$user) {
        throw new \RuntimeException('Utilisateur avec le nom "René Lataupe" introuvable.');
    }

    return $user;
}

protected function getAdmin(): User
{
    /** @var UserRepository $userRepository */
    $userRepository = self::getContainer()->get(UserRepository::class);

    $admin = $userRepository->findOneBy(['admin' => true]);

    if (!$admin) {
        throw new \RuntimeException('Aucun utilisateur admin trouvé dans les fixtures.');
    }

    return $admin;
}

    protected function createMediaWithFile(?User $user = null): \App\Entity\Media
    {
        $user ??= $this->getIna(); // utilise Inatest par défaut
        $album = $this->getAlbumForUser($user);

        $media = new \App\Entity\Media();
        $media->setTitle('Media Test');
        $media->setUser($user);
        $media->setAlbum($album);
        $media->setPath('uploads/' . uniqid() . '.jpg');

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }


    protected function getDoctrine(): ManagerRegistry
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');
        return $doctrine;
    }

    protected function getIna(): User
    {
        /** @var UserRepository $repo */
        $repo = $this->getDoctrine()->getRepository(User::class);

        $ina = $repo->findOneBy(['name' => 'Inatest Zaoui']);

        $this->assertNotNull($ina, 'L\'utilisateur "Inatest Zaoui" est introuvable.');
        $this->assertTrue($ina->isAdmin(), 'L’utilisateur "Inatest Zaoui" n’est pas admin.');

        return $ina;
    }


    protected function getInvite(): User
    {
        /** @var UserRepository $repo */
        $repo = $this->getDoctrine()->getRepository(User::class);
        $invite = $repo->findOneBy(['admin' => false], ['id' => 'ASC']);

        $this->assertNotNull($invite, 'Aucun utilisateur invité trouvé en base.');
        $this->assertFalse($invite->isAdmin(), 'L’utilisateur trouvé est un admin alors qu’un invité était attendu.');

        return $invite;
    }
protected function getBlockedUser(): User
{
    $userRepo = $this->getDoctrine()->getRepository(User::class);

    // L’ID du user_blocked est connu car la référence est persistée, donc on le retrouve directement
    $user = $userRepo->findOneBy(['email' => 'blocked@example.com']);

    $this->assertNotNull($user, 'L’utilisateur bloqué (user_blocked) est introuvable.');
    $this->assertTrue($user->isBlocked(), 'L’utilisateur bloqué n’est pas marqué comme bloqué.');

    return $user;
}


protected function getAlbumForUser(User $user): Album
{
    $userId = $user->getId();

    // On recharge l'utilisateur depuis l'EntityManager courant
    $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

    $albumRepo = $this->getDoctrine()->getRepository(Album::class);
    $album = $albumRepo->findOneBy(['user' => $user]);

    $this->assertNotNull($album, 'Aucun album trouvé pour l’utilisateur.');

    return $album;
}

    
    /**
     * Charge les fixtures par défaut nécessaires à la majorité des tests
     */
    protected function loadDefaultFixtures(): void
    {
        $this->loadFixtures([
            \App\DataFixtures\UserFixtures::class,
            \App\DataFixtures\AlbumFixtures::class,
            \App\DataFixtures\MediaFixtures::class,
        ], static::getContainer());
    }

    /**
     * @param string[] $fixtureServiceIds
     */
    protected function loadFixtures(array $fixtureServiceIds, ContainerInterface $container): void
    {
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);

        $fixtures = [];
        foreach ($fixtureServiceIds as $serviceId) {
            $fixture = $container->get($serviceId);
            if (!$fixture instanceof FixtureInterface) {
                throw new \RuntimeException("Le service '$serviceId' doit implémenter FixtureInterface.");
            }
            $fixtures[] = $fixture;
        }

        $executor->execute($fixtures);
    }

        protected function deleteFileIfExists(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

       protected function createTempImage(): UploadedFile
    {
        $source = __DIR__ . '/fixtures/sample.jpg';
        if (!file_exists($source)) {
            imagejpeg(imagecreatetruecolor(1, 1), $source);
        }
        $target = sys_get_temp_dir() . '/img_' . uniqid() . '.jpg';
        copy($source, $target);
        return new UploadedFile($target, 'sample.jpg', 'image/jpeg', null, true);
    }

    

    
}
