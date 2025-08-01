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

abstract class CustomWebTestCase extends WebTestCase
{
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
        $ina = $repo->findOneBy([], ['id' => 'ASC']);
        $this->assertNotNull($ina, 'Aucun utilisateur trouvé en base.');
        $this->assertTrue($ina->isAdmin(), 'L’utilisateur trouvé n’est pas admin.');
        return $ina;
    }

    protected function getAlbumForUser(User $user): Album
    {
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
}
