<?php

namespace App\Repository;

use App\Entity\DatabaseYGO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DatabaseYGO>
 *
 * @method DatabaseYGO|null find($id, $lockMode = null, $lockVersion = null)
 * @method DatabaseYGO|null findOneBy(array $criteria, array $orderBy = null)
 * @method DatabaseYGO[]    findAll()
 * @method DatabaseYGO[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DatabaseYGORepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DatabaseYGO::class);
    }

    //    /**
    //     * @return DatabaseYGO[] Returns an array of DatabaseYGO objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DatabaseYGO
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
