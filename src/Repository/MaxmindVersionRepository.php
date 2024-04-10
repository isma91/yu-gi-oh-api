<?php

namespace App\Repository;

use App\Entity\MaxmindVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaxmindVersion>
 *
 * @method MaxmindVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaxmindVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaxmindVersion[]    findAll()
 * @method MaxmindVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaxmindVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaxmindVersion::class);
    }

    //    /**
    //     * @return MaxmindVersion[] Returns an array of MaxmindVersion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MaxmindVersion
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
