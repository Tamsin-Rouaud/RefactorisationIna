<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class CustomWebTestCase extends WebTestCase
{
    /**
 * @param string[] $fixtureServiceIds
 */
    protected function loadFixtures(array $fixtureServiceIds, ContainerInterface $container): void
    {
        $em = $container->get(EntityManagerInterface::class);

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);

        $fixtures = [];
        foreach ($fixtureServiceIds as $serviceId) {
            $fixture = $container->get($serviceId);
            $fixtures[] = $fixture;
        }

        $executor->execute($fixtures);
    }
}
