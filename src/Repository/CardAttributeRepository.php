<?php

namespace App\Repository;

use App\Entity\CardAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardAttribute>
 *
 * @method CardAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method CardAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method CardAttribute[]    findAll()
 * @method CardAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardAttribute::class);
    }

    //    /**
    //     * @return CardAttribute[] Returns an array of CardAttribute objects
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

    //    public function findOneBySomeField($value): ?CardAttribute
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
