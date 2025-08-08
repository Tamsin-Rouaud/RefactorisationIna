<?php

// namespace App\Security;

// use App\Entity\User;
// use Symfony\Component\Security\Core\User\UserCheckerInterface;
// use Symfony\Component\Security\Core\User\UserInterface;
// use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

// class UserChecker implements UserCheckerInterface
// {
//     public function checkPreAuth(UserInterface $user): void
//     {
//         if (!$user instanceof User) {
//             return;
//         }

//         if ($user->isBlocked()) {
//             throw new CustomUserMessageAccountStatusException('Votre compte a été bloqué.');
//         }
//     }

//     public function checkPostAuth(UserInterface $user): void
//     {
        
//     }
// }


namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été bloqué.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Si nécessaire, tu peux ajouter ici une logique post-authentification
    }
}
