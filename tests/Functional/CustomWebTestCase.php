<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\FixtureInterface;
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
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        /** @var ORMPurger $purger */
        $purger = new ORMPurger($em);

        /** @var ORMExecutor $executor */
        $executor = new ORMExecutor($em, $purger);

        /** @var list<FixtureInterface> $fixtures */
        $fixtures = [];
        foreach ($fixtureServiceIds as $serviceId) {
            $fixture = $container->get($serviceId);
            if (!$fixture instanceof FixtureInterface) {
                throw new \RuntimeException("Le service '$serviceId' doit implémenter FixtureInterface.");
            }
            $fixtures[] = $fixture;
        }

        $executor->execute($fixtures);
    }
}
