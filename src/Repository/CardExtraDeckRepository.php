<?php

namespace App\Repository;

use App\Entity\CardExtraDeck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardExtraDeck>
 *
 * @method CardExtraDeck|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardExtraDeck|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardExtraDeck[]    findAll()
 * @method CardExtraDeck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardExtraDeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardExtraDeck::class);
    }

    //    /**
    //     * @return CardExtraDeck[] Returns an array of CardExtraDeck objects
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

    //    public function findOneBySomeField($value): ?CardExtraDeck
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
