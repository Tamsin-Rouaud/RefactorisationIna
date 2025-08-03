<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;


class UserCheckerTest extends TestCase
{
    public function testCheckPreAuthWithActiveUser(): void
    {
        $user = new User();
        $user->setIsBlocked(false);

        $checker = new UserChecker();

        // Aucun comportement attendu = pas d’exception
        $this->expectNotToPerformAssertions();
        $checker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithBlockedUser(): void
    {
        $user = new User();
        $user->setIsBlocked(true);

        $checker = new UserChecker();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte a été bloqué.');

        $checker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithUnsupportedUser(): void
    {
        $checker = new UserChecker();

        $mock = $this->createMock(UserInterface::class);

        // Aucun comportement attendu, on veut juste passer le "return"
        $this->expectNotToPerformAssertions();
        $checker->checkPreAuth($mock);
    }

}