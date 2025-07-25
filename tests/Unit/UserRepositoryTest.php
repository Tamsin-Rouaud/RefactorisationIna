<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(UserRepository::class);
    }

    public function testUpgradePasswordWithValidUser(): void
    {
        // Crée un utilisateur
        $user = new User();
        $user->setName('TestUser');
        $user->setEmail('test@example.com');
        $user->setPassword('old_password');
        $this->em->persist($user);
        $this->em->flush();

        // Nouvelle version du mot de passe
        $newHashedPassword = 'new_hashed_password';

        $this->repository->upgradePassword($user, $newHashedPassword);

        // Recharge l’utilisateur depuis la base
        $updatedUser = $this->repository->find($user->getId());
        $this->assertSame($newHashedPassword, $updatedUser->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionWithInvalidUser(): void
    {
        $stub = $this->createStub(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->repository->upgradePassword($stub, 'irrelevant');
    }
}
