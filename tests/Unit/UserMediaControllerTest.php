<?php

namespace App\Tests\Unit;

use App\Controller\UserMediaController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;

class UserMediaControllerTest extends TestCase
{
    public function testControllerIsInstantiated(): void
    {
        $paramBag = new ParameterBag(['upload_dir' => 'uploads']);
        $controller = new UserMediaController($paramBag);

        $this->assertInstanceOf(UserMediaController::class, $controller);
    }
}