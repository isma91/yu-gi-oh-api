<?php

namespace App\Repository;

use App\Entity\CardMainDeck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardMainDeck>
 *
 * @method CardMainDeck|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardMainDeck|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardMainDeck[]    findAll()
 * @method CardMainDeck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardMainDeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardMainDeck::class);
    }

    //    /**
    //     * @return CardMainDeck[] Returns an array of CardMainDeck objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CardMainDeck
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
