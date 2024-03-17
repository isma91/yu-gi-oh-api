<?php

namespace App\Repository;

use App\Entity\CardSideDeck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardSideDeck>
 *
 * @method CardSideDeck|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardSideDeck|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardSideDeck[]    findAll()
 * @method CardSideDeck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardSideDeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardSideDeck::class);
    }

    //    /**
    //     * @return CardSideDeck[] Returns an array of CardSideDeck objects
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

    //    public function findOneBySomeField($value): ?CardSideDeck
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
