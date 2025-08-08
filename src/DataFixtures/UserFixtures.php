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

        // Admin bloqué pour tester le portfolio inaccessible
        $blockedAdmin = new User();
        $blockedAdmin->setName('Admin Bloqué');
        $blockedAdmin->setEmail('admin.bloque@example.com');
        $blockedAdmin->setPassword($this->passwordHasher->hashPassword($blockedAdmin, 'password'));

        $blockedAdmin->setAdmin(true);
        $blockedAdmin->setIsBlocked(true);

        $manager->persist($blockedAdmin);


        // Utilisateur actif
        $invite1 = new User();
        $invite1->setEmail('invite1@example.com');
        $invite1->setName('Jean Dupont');
        $invite1->setAdmin(false);
        $invite1->setIsBlocked(false);
        $invite1->setPassword($this->passwordHasher->hashPassword($invite1, 'password'));
        $manager->persist($invite1);
        $this->addReference('user_invite1', $invite1);

        $invite3 = new User();
        $invite3->setEmail('invite3@example.com');
        $invite3->setName('Aline Giraud');
        $invite3->setAdmin(false);
        $invite3->setIsBlocked(false);
        $invite3->setPassword($this->passwordHasher->hashPassword($invite3, 'password'));
        $manager->persist($invite3);
        $this->addReference('user_invite3', $invite3);

        $invite4 = new User();
        $invite4->setEmail('invite4@example.com');
        $invite4->setName('René Lataupe');
        $invite4->setAdmin(false);
        $invite4->setIsBlocked(false);
        $invite4->setPassword($this->passwordHasher->hashPassword($invite4, 'password'));
        $manager->persist($invite4);
        $this->addReference('user_invite4', $invite4);

        $invite5 = new User();
        $invite5->setEmail('invite5@example.com');
        $invite5->setName('Elodie Martin');
        $invite5->setAdmin(false);
        $invite5->setIsBlocked(false);
        $invite5->setPassword($this->passwordHasher->hashPassword($invite5, 'password'));
        $manager->persist($invite5);
        $this->addReference('user_invite5', $invite5);

        // Utilisateur bloqué
        $invite2 = new User();
        $invite2->setEmail('invite2@example.com');
        $invite2->setName('Marie Durand');
        $invite2->setAdmin(false);
        $invite2->setIsBlocked(true);
        $invite2->setPassword($this->passwordHasher->hashPassword($invite2, 'password'));
        $manager->persist($invite2);
        $this->addReference('user_invite2', $invite2);

        $invite6 = new User();
        $invite6->setEmail('invite6@example.com');
        $invite6->setName('Lucie Cromagnon');
        $invite6->setAdmin(false);
        $invite6->setIsBlocked(true);
        $invite6->setPassword($this->passwordHasher->hashPassword($invite6, 'password'));
        $manager->persist($invite6);
        $this->addReference('user_invite6', $invite6);

        $blockedUser = new User();
$blockedUser->setName('Utilisateur bloqué');
$blockedUser->setIsBlocked(true);
$blockedUser->setEmail('blocked@example.com');
$blockedUser->setPassword($this->passwordHasher->hashPassword($blockedUser, 'password'));
$manager->persist($blockedUser);
$this->addReference('user_blocked', $blockedUser);
        $manager->flush();
    }
}
