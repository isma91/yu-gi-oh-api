<?php

namespace App\Repository;

use App\Entity\Rarity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rarity>
 *
 * @method Rarity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rarity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rarity[]    findAll()
 * @method Rarity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RarityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rarity::class);
    }

    //    /**
    //     * @return Rarity[] Returns an array of Rarity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Rarity
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
