<?php

namespace App\Tests\Entity;

use App\Entity\CardCollection;
use App\Entity\CardPicture;
use App\Entity\Deck;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Exception\NotSupported;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractTestEntity extends KernelTestCase
{
    public static ?EntityManager $em = NULL;

    public static function initiateEm(): void
    {
        if (self::$em === NULL) {
            $kernel = self::bootKernel();
            self::$em = $kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        }
    }

    /**
     * @param string $className
     * @return object|CardPicture|Deck|CardCollection
     * @throws NotSupported
     * @throws EntityNotFoundException
     */
    public static function getOneEntity(string $className): object
    {
        self::initiateEm();
        $result = self::$em->getRepository($className)->findBy([], NULL, 1);
        if (empty($result) === TRUE) {
            throw new EntityNotFoundException("No entity from " . $className . " founded, maybe you forgot to run the Import before testing ??");
        }
        return $result[0];
    }
}