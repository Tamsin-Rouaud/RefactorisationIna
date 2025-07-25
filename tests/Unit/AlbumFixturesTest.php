<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AlbumFixtures;
use App\DataFixtures\UserFixtures;
use PHPUnit\Framework\TestCase;

class AlbumFixturesTest extends TestCase
{
    public function testGetDependenciesReturnsCorrectClass(): void
    {
        $fixtures = new AlbumFixtures();

        $expected = [
            UserFixtures::class,
        ];

        $this->assertEquals($expected, $fixtures->getDependencies());
    }
}