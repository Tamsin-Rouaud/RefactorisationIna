<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\MediaFixtures;
use App\DataFixtures\UserFixtures;
use App\DataFixtures\AlbumFixtures;
use PHPUnit\Framework\TestCase;



class MediaFixturesTest extends TestCase
{
    public function testGetDependenciesReturnsCorrectClasses(): void
    {
        $fixtures = new MediaFixtures();

        $expected = [
            UserFixtures::class,
            AlbumFixtures::class,
        ];

        $this->assertEquals($expected, $fixtures->getDependencies());
    }
}