<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Admin Ina
        $ina = new User();
        
        $ina->setEmail('ina@example.com');
        $ina->setName('Inatest Zaoui');
        $ina->setAdmin(true);
        $ina->setIsBlocked(false);
        $ina->setPassword($this->passwordHasher->hashPassword($ina, 'password'));
        $manager->persist($ina);
        $this->addReference('user_ina', $ina);

        // Utilisateur actif
        $invite1 = new User();
        $invite1->setEmail('invite1@example.com');
        $invite1->setName('Jean Dupont');
        $invite1->setAdmin(false);
        $invite1->setIsBlocked(false);
        $invite1->setPassword($this->passwordHasher->hashPassword($invite1, 'password'));
        $manager->persist($invite1);
        $this->addReference('user_invite1', $invite1);

        // Utilisateur bloqué
        $invite2 = new User();
        $invite2->setEmail('invite2@example.com');
        $invite2->setName('Marie Durand');
        $invite2->setAdmin(false);
        $invite2->setIsBlocked(true);
        $invite2->setPassword($this->passwordHasher->hashPassword($invite2, 'password'));
        $manager->persist($invite2);
        $this->addReference('user_invite2', $invite2);

        $manager->flush();
    }
}
