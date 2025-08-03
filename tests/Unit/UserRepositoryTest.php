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
        
        /** @var \Doctrine\Persistence\ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        $em = $registry->getManager();
        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Le manager Doctrine n’est pas un EntityManagerInterface.');
        }

        $this->em = $em;

        /** @var UserRepository $repo */
        $repo = $registry->getRepository(User::class);
        $this->repository = $repo;
    }

    public function testUpgradePasswordWithValidUser(): void
    {
        $user = new User();
        $user->setName('TestUser_' . uniqid());
        $user->setEmail('test_' . uniqid() . '@example.com');
        $user->setPassword('old_password');

        $this->em->persist($user);
        $this->em->flush();

        $newHashedPassword = 'new_hashed_password';

        $this->repository->upgradePassword($user, $newHashedPassword);

        /** @var User|null $updatedUser */
        $updatedUser = $this->repository->find($user->getId());

        $this->assertNotNull($updatedUser);
        $this->assertSame($newHashedPassword, $updatedUser->getPassword());
    }


   public function testUpgradePasswordThrowsExceptionWithInvalidUser(): void
    {
        $stub = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): string
            {
                return 'irrelevant';
            }
        };

        $this->expectException(UnsupportedUserException::class);
        $this->repository->upgradePassword($stub, 'irrelevant');
    }

}
